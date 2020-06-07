<?php
/*
**	Rose\Cookies
**
**	Copyright (c) 2018-2020, RedStar Technologies, All rights reserved.
**	https://rsthn.com/
**
**	THIS LIBRARY IS PROVIDED BY REDSTAR TECHNOLOGIES "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
**	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A 
**	PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL REDSTAR TECHNOLOGIES BE LIABLE FOR ANY
**	DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT 
**	NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; 
**	OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, 
**	STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
**	USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

namespace Rose;

use Rose\Configuration;
use Rose\Gateway;
use Rose\Text;
use Rose\DateTime;

/*
**	Stores and retrieves persistent system parameters.
*/

class Cookies
{
	/*
	**	Primary and only instance of this class.
	*/
    private static $objectInstance = null;

	/*
	**	Returns the instance of this class.
	*/
    public static function getInstance ()
    {
		if (Cookies::$objectInstance == null)
			Cookies::$objectInstance = new Cookies();

        return Cookies::$objectInstance;
    }

	/*
	**	Constructs the cookies object, this is a private constructor as this class can have only one instance.
	*/
    private function __construct ()
    {
		// Loads cookies from its configuration section if present.
        if (Configuration::getInstance()->has('Cookies'))
        {
            foreach (Configuration::getInstance()->Cookies->__nativeArray as $name => $value)
            {
                if (!$this->has ($name))
                    $this->set ($name, Text::format ($value));
			}
        }
    }

	/*
	**	Returns true if the given cookie exists.
	*/
    public function has ($name)
    {
        return Gateway::getInstance()->cookies->has($name);
    }

	/*
	**	Returns the cookie value matching the given name or null if not found.
	*/
    public function get ($name)
    {
        return Gateway::getInstance()->cookies->get($name);
    }

	/*
	**	Sets a cookie with optional expiration value (delta from current time), the cookie is added to the
	**	cookies of the Gateway.
	*/
    private function setCookieHeader ($name, $value, $ttl=null, $domain=null, $path=null)
    {
		$path = $path == null ? Gateway::getInstance()->root : $path;
		$domain = $domain == null ? Configuration::getInstance()->Gateway->server_name : $domain;

		if ($value === null)
		{
			$ttl = -608400;
			$value = 'deleted';
		}

		$header = $name.'='.$value;

		if ($ttl > 0)
			$header .= '; Expires=' . (new DateTime())->add($ttl, DateTime::SECOND)->format('UTC');
		else if ($ttl < 0)
			$header .= '; MaxAge=-1; Expires=Thu, 01 Jan 1970 00:00:00 GMT';

		if ($domain) $header .= '; Domain='.$domain;
		if ($path) $header .= '; Path='.$path;

		if (Configuration::getInstance()->Gateway->same_site)
			$sameSite = Configuration::getInstance()->Gateway->same_site;
		else
			$sameSite = 'None';

		$header .= '; SameSite='.$sameSite;

		if ($sameSite == 'None' && Gateway::getInstance()->secure)
			$header .= '; Secure';
			
		$header .= '; HttpOnly';

		header('Set-Cookie: '.$header);
	}

	/*
	**	Sets a cookie with optional ttl value (time to live from current time), the cookie is added to the
	**	cookies of the Gateway.
	*/
    public function set ($name, $value, $ttl=null, $domain=null)
    {
        if (!$name) return;

		if ($value == null)
		{
			$this->remove ($name, $domain);
			return;
		}

		if ($ttl !== null)
			$this->setCookieHeader ($name, $value, $ttl, $domain);
		else
			$this->setCookieHeader ($name, $value, 0, $domain);
    }

	/*
	**	Similar to set() but the request parameters of Gateway will not be modified.
	*/
    public function setCookie ($name, $value, $ttl=null, $domain=null)
    {
		if (!$name) return;

        if ($value == null)
        {
			$this->remove ($name, $domain);
            return;
		}

		if ($ttl !== null)
			$this->setCookieHeader ($name, $value, $ttl, $domain);
		else
			$this->setCookieHeader ($name, $value, 0, $domain);
    }

	/*
	**	Removes a cookie given its name.
	*/
    public function remove ($name, $domain=null)
    {
        if (!$name) return;

		$this->setCookieHeader ($name, null, 0, $domain);

        Gateway::getInstance()->cookies->remove($name);
    }

	/*
	**	Returns the value of the given cookie. Or null if the cookie doesn't exist.
	*/
    public function __get ($name)
    {
		return $this->get ($name);
    }

	/*
	**	Sets the value of a cookie.
	*/
    public function __set ($name, $value)
    {
        $this->set ($name, $value);
    }
};

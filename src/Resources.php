<?php
/*
**	Rose\Resources
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

use Rose\Map;
use Rose\Arry;
use Rose\Error;

/*
**	Provides an interface to register, unregister and retrieve system resources. One major advantage of using system resources is that they can be
**	created only when they are required, this is done by using resource constructors which are registered using this class as well.
*/

class Resources
{
	/*
	**	Primary and only instance of this class.
	*/
	private static $objectInstance = null;

	/*
	**	List of available resources.
	*/
	private $resourceContainer;
	
	/*
	**	List of resource constructors, when a non-existant resource is accessed the proper constructor will be invoked if found.
	*/
    private $resourceConstructor;

    public function __construct ()
    {
        $this->resourceContainer= new Map ();
        $this->resourceConstructor= new Map ();
    }

	/*
	**	Returns the instance of this class.
	*/
    public static function getInstance ()
    {
        if (Resources::$objectInstance == null)
        {
            Resources::$objectInstance = new Resources();
            Resources::$objectInstance->register ('Vars', new Map ());
		}

        return Resources::$objectInstance;
    }

	/*
	**	Adds the resource to the list of available resources using the specified name.
	*/
    public function register ($name, $resource)
    {
        $this->resourceContainer->set ($name, $resource);
        return $resource;
    }

	/*
	**	Registers a constructor for the resource with the given name such that when the resources is requested and it does not exist, the
	**	specified `$method` of the given `$object` will be executed and the return value will be registered as a resource. However when
	**	the `$dynamic` flag is set, the returned resource will *not* be registered, but rather just returned to ensure that if another
	**	request for the same resource is made, the constructor will end up being called again.
	*/
    public function registerConstructor ($name, $object, $method, $dynamic=false)
    {
        $this->resourceConstructor->set ($name, Arry::fromNativeArray(array($object, $method, $dynamic), false));
    }

	/*
	**	Removes a resource from the list of available resources.
	*/
    public function unregister ($name)
    {
        $this->resourceContainer->remove($name);
    }

	/*
	**	Unregisters a resource constructor.
	*/
    public function unregisterConstructor ($name)
    {
        $this->resourceConstructor->remove($name);
    }

	/*
	**	Returns boolean indicating if the specified resource exists. Note that if the resource is not immediately available but
	**	a resource constructor is registered for it and `$immediately` is false then `true` will still be returned.
	*/
    public function exists ($name, $immediately=false)
    {
        if (!$this->resourceContainer->has($name))
        {
            if (!$immediately && $this->resourceConstructor->get($name) != null)
                return true;

            return false;
		}

        return true;
    }

	/*
	**	Retrieves a resource given its name. If the resource does not exist but a constructor is registered, then the constructor will be
	**	invoked to create the resource.
	*/
    public function retrieve ($name)
    {
        if (!$this->resourceContainer->has($name))
        {
			$info = $this->resourceConstructor->get($name);

            if ($info == null)
                throw new Error ('Undefined resource: '.$name);

            if ($info->get(2) == true)
                return $info->get(0)->{$info->get(1)} ();

            $this->register ($name, $info->get(0)->{$info->get(1)} ());
		}

        return $this->resourceContainer->get($name);
    }

	/*
	**	Returns a resource given its name.
	*/
    public function __get ($name)
    {
        switch ($name)
        {
            case 'Keys':
				return $this->resourceContainer->keys();

            case 'Null':
				return '<NULL>';
		}

		return $this->retrieve($name);
    }

	/*
	**	Registers a resource with the specified name. Equivalent to calling register().
	*/
    public function __set ($name, $value)
    {
        $this->register ($name, $value);
    }
};

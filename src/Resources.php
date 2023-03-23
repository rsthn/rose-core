<?php

namespace Rose;

use Rose\Errors\Error;

use Rose\Map;
use Rose\Arry;

/*
**	Provides an interface to register, unregister and retrieve system resources. The advantage of using system resources is that they can be
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
	**	specified function will be executed and the return value will be registered as a resource.
	**	
	**	When the `$dynamic` flag is set the returned resource will *not* be registered, but rather just returned to ensure that if another
	**	request for the same resource is made, the constructor will end up being called again.
	*/
    public function registerConstructor ($name, $function, $dynamic=false)
    {
        $this->resourceConstructor->set ($name, new Arry (array($function, $dynamic), false));
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

            if ($info->get(1) == true)
                return $info->get(0) ();

            $this->register ($name, $info->get(0) ());
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

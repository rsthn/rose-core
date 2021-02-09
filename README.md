## About Rose

<p align="center">
	<a href="https://packagist.org/packages/rsthn/rose-core"><img src="https://poser.pugx.org/rsthn/rose-core/downloads" alt="Total Downloads"></a>
	<a href="https://packagist.org/packages/rsthn/rose-core"><img src="https://poser.pugx.org/rsthn/rose-core/v" alt="Latest Stable Version"></a>
	<a href="https://packagist.org/packages/rsthn/rose-core"><img src="https://poser.pugx.org/rsthn/rose-core/license" alt="License"></a>
</p>

More information to be added soon.

<br/>&nbsp;
## Configuration

Ensure you have a `rose-env` file on the root of this project with the name of the appropriate configuration environment to use (i.e. `dev`, `prod`, etc). Rose will load the `dev.conf`, `prod.conf` or whichever file you specify (along with `system.conf` by default) from the `rcore` folder. Additionally, edit the `rcore/*.conf` files to reflect the configuration of your system.

_Note that the `rose-env` file should not be commited to ensure it is never overwritten in destination servers._

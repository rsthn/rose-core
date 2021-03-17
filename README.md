<p align="center">
	<a href="https://packagist.org/packages/rsthn/rose-core"><img src="https://poser.pugx.org/rsthn/rose-core/downloads" alt="Total Downloads"></a>
	<a href="https://packagist.org/packages/rsthn/rose-core"><img src="https://poser.pugx.org/rsthn/rose-core/v" alt="Latest Stable Version"></a>
	<a href="https://packagist.org/packages/rsthn/rose-core"><img src="https://poser.pugx.org/rsthn/rose-core/license" alt="License"></a>
</p>

# About Rose

Rose is a framework designed aiming to the highest-level of abstraction possible, this is achieved by using an internal expression language (dialect of Lisp) to code entire web services and systems. This effectively allows the developer to **never write any host language code** when building core logic.

The host language is the language on which the `rose-core` was built, for the repository you're viewing right now, that would be PHP.

In the future we're planning to build ports of Rose and its extensions for other host languages such as Python, NodeJS or whatever hot crazy lang may be available in the future. And when that happens, all of your current code written for Rose will **continue to work seamlessly** in the new host language (given that all required extensions are _of course_ ported to the new host language as well).

And _that_ dear readers, is the power of Rose.

<br/>

# Installation

As any other package of the modern web, this one should be installed using a package manager. In this case, we're on Packagist and you can install Rose using composer.

**However** this is `rose-core`, and should not be used alone. Please use one of our pre-made project skeletons instead:

|Project Type|Package URL|Description|
|------------|-----------|-----------|
|Web Service|[https://github.com/rsthn/rose-webservice](https://github.com/rsthn/rose-webservice)|Deploy flexible and secure web services in the blink of an eye with Rose's internal web-service extension, Wind.

<br/>

# FAQ

**Q: If there is no need to write host-language code, what happens when I need something new that is not doable using current expressions?**

A: Given such case, you're free to build your own Rose-extensions in the host-language, and use it in any expression. Read our extensions documentation for more information.

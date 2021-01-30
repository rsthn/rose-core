# Wind Web-Service Extension

This extension adds web-service support to [Rose](https://github.com/rsthn/rose-core).

# Installation

```sh
composer require rsthn/rose-ext-wind
```

# Multi-Response Mode

Multi-response can be used to run multiple Wind requests (currently up to 16) in a single web request. To use this feature use the `rpkg` parameter via GET/POST. This `rpkg` parameter is a list of semi-colon separated `id,data` pairs, where `id` is the name you want the request to have when returned, and `data` is the Base64 encoded request parameters.

For example, consider the following value for `rpkg`:

```
r0,Zj11c2Vycy5jb3VudA==;r1,Zj11c2Vycy5saXN0;
```

It describes two requests, `r0` and `r1`. The first (`r0`) one is a Wind request using parameters `f=users.count` (just Base64 decode the data), and the second one (`r1`) has parameters `f=users.list`.

This request will effectively run two Wind requests to functions `users.count` and `users.list` and the result is returned as follows:

```json
{
  "response": 200,
  "r0": {
    "response": 200,
    "count": 1
  },
  "r1": {
    "response": 200,
    "data": [
      {"id": "1", "username": "admin", "name": "Administrator"} 
    ]
  }
}
```

As it can be noted, the result object contains the results of both calls identified respective by their `id` (as specified in the `rpkg`).

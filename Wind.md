# Wind API Behavior

Below are the guidelines required to create an API compliant with Wind. A service made compatible with this behavior is very useful because certain applications and libraries are expecting Wind-compliant interfaces (for instance, [Riza](https://github.com/rsthn/riza)), and could make front-end development or integrations much easier.

We do not enforce any language for this purpose, however if you'd like to set sail into new waters please consider using [Rose](https://github.com/rsthn/rose-api) for your project.

<br/>

# Requests

- Requests should be sent as regular request parameters using either HTTP method (GET or POST) to the API end-point, with the `Content-Type` header set to `application/x-www-form-urlencoded` or `multipart/form-data` (use the latter when files are uploaded to the service).

- The `f` request parameter is mandatory and indicates the name of the function to execute, this parameter can have only the characters `[#A-Za-z0-9.,_-]`, any other character will be removed.

<br/>

# Responses

Responses are always in JSON format (unless otherwise explicitly specified) with a mandatory integer field named `response` which indicates the response code. Wind describes several standard response codes as follows:

|Response Code|Short Name|Details|
|-------------|----------|-----------|
|200|OK|Request completed without errors.
|400|BAD_REQUEST|The respective handler for the function name in parameter `f` was not found.
|401|UNAUTHORIZED|Function requires the invoker to be an authenticated user.
|403|FORBIDDEN|Function requires the invoker to have certain permission (i.e. `admin`).
|404|NOT_FOUND|A requested resource could not be found.
|405|METHOD_NOT_ALLOWED|The HTTP method used is not allowed by the function.
|422|VALIDATION_ERROR|One or more request fields did not pass validation checks. A field named `fields` might be found in the response, this is an object with the offending request parameter name(s) and their respective validation error message.
|409|CUSTOM_ERROR|A field named `error` in the response will have the complete error message.

<br/>

# Multi-Request Mode

This mode can be used to run multiple requests (maximum is implementation-dependant, however we recommend 16 as limit) in a single web-request. To use this feature use the special `rpkg` parameter which is a list of semi-colon separated `id,data` pairs, where `id` is the name you want the response to have when returned, and `data` is the Base64 encoded request parameters.

For example, consider the following value for `rpkg`:

```
r0,Zj11c2Vycy5jb3VudA==;r1,Zj11c2Vycy5saXN0;
```

- It describes two requests, `r0` and `r1`.
- The first request (r0) has parameters `f=users.count` (obtained by Base64 decoding the data),
- And the second one (r1) has parameters `f=users.list`.

This effectively causes Wind to execute functions `users.count` and `users.list` (let's assume the first returns count=1 and the second returns an array with one user).

The response will be returned as follows:

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

Note that the result object contains the results of both calls identified respectively by their `id` (as specified manually by the invoker in the `rpkg` field). This can use useful to batch API calls together and reduce server round-trip times.

<br/>

# Data Sources

A data source is a set of functions that provide access to a certain dataset (most commonly on a database). Usually named in plural for semantic consistency.

When a service indicates in its API documentation that certain `function prefix` is for a datasource (i.e. `candies`) it means that certain functions will be available under that prefix (i.e. `candies.add`).

There are different types of data source, each describing how many functions are available under some function prefix. From minimal to full-access, see table below:

|Data Source Type|`add`|`update`|`count`|`list`|`get`|`delete`|`enum`|Filter Support|Sort Support|
|----------------|---|------|-----|----|---|------|----|----|----|
|Full Access     |✅ |✅   |✅   |✅  |✅ |✅   |✅  |✅  |✅  |
|Enumerator Only |   |      |     |    |   |      |✅  |    |     |
|Read Only       |   |      |✅   |✅  |✅ |     |    |✅  |✅    |
|Minimal         |   |      |✅   |✅  |   |     |    |    |     |

<br/>

Therefore, say we have a full-access data source named `candies`, the following are the API functions that it should expose:

- [candies.add](#candies.add)
- [candies.update](#candies.update)
- [candies.count](#candies.count)
- [candies.list](#candies.list)
- [candies.get](#candies.get)
- [candies.delete](#candies.delete)
- [candies.enum](#candies.enum)

All of which, will be described in detail in the following section.

<br/>

# Data Source Functions

### `candies.add`

Used to create a new item in the dataset.

### Request
- **Form Parameters**: Fields required by the dataset to create a new item.

### Response
- Standard response.

<br/>

### `candies.update`

Used to update the details of a previously created item.

### Request
- **ID**: Parameter `id`, indicating the primary key of the item that the caller wishes to update.
- **Form Parameters**: Fields required by the dataset to update an item.

### Response
- Standard response.

<br/>

### `candies.count`

Used to obtain the count of items in the dataset given certain filter.

#### Request
- **Data Filters**: Zero or more filtering parameters, each of which should start with `f_` to differentiate them from standard parameters.

#### Response
- Field `count` (integer) with the number of items in the dataset.

<br/>

### `candies.list`

Used to obtain a list of items from the dataset that match certain filter.

#### Request
- **Data Ordering**: Two optional parameters `sort` and `order`. The first indicates the name of the column to sort by, and the later indicates the ordering mechanism (`asc` or `desc`).
- **Pagination**: Two optional parameters `offset` and `count` which are used to limit the result set.
- **Data Filters**: Zero or more filtering parameters, each of which should start with `f_` to differentiate them from standard parameters.

#### Response
- Field `data` (array) with the items in the dataset matching the active filter and pagination range. Primary key of items should be returned as field `id` for consistency.

<br/>

### `candies.get`

Used to retrieve the data of a single item from the dataset given its `id`.

### Request
- **Data Filters**: Zero or more filtering parameters, each of which should start with `f_` to differentiate them from standard parameters.
- **ID**: Parameter `id`, indicating the primary key of the item that the caller wishes to retrieve.

#### Response
- Field `data` (array) with a single object with the details of the item matching the `id`.

<br/>

### `candies.delete`

Used to delete an item from a dataset given its `id`.

### Request
- **Data Filters**: Zero or more filtering parameters, each of which should start with `f_` to differentiate them from standard parameters.
- **ID**: Parameter `id`, indicating the primary key of the item that the caller wishes to delete.

### Response
- Standard response.

<br/>

### `candies.enum`

Used to obtain a full list of items from the dataset in their shortest form (useful to populate dropdowns).

#### Request
- **Data Filters**: Zero or more filtering parameters, each of which should start with `f_` to differentiate them from standard parameters.

#### Response
- Field `data` (array) with the items in the dataset matching the active filter. Each item should have at least two fields `id` (to identify the item) and `label` (indicating the friendly name of the item).

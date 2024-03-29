[&laquo; Go Back](./Expr.md)
# Database


#### Escapes a value to be used in SQL queries. Uses the connection's driver escape function when necessary.
```lisp
(db:escape "Jack O'Neill")
; 'Jack O''Neill'
```

#### Uses the connection's driver to escape the given value considering it to be a column/table name.
```lisp
(db:escape-name "First Name")
; `First Name`
```

#### Executes a query and returns a scalar value, that is, first column of first row or `null` if no rows are returned.
```lisp
(db:scalar `SELECT COUNT(*) FROM users WHERE name LIKE ?` "Jack%"))
; 3
```

#### Executes a query and returns an array with scalars value (all rows, first column).
```lisp
(db:scalars `SELECT name, last_name FROM users WHERE age > ?` 18)
; ["Jack", "Daniel", "Samantha"]
```

#### Executes a query and returns a map with the first row.
```lisp
(db:row `SELECT name, last_name FROM users WHERE age >= ?` 21)
; {"name": "Jack", "last_name": "O'Neill"}
```

#### Executes a query and returns an array with the first row, values only.
```lisp
(db:row-values `SELECT name, last_name FROM users WHERE age >= ?` 21)
; ["Jack", "O'Neill"]
```

#### Executes a query and returns an array with all the resulting rows.
```lisp
(db:table `SELECT name FROM super_users WHERE age >= ?` 18)
; [{"name": "Jack"}, {"name": "Daniel"}, {"name": "Samantha"}]
```

#### Executes a query and returns an array with row values.
```lisp
(db:table-values `SELECT name, last_name FROM super_users WHERE status=?` "active")
; [["Jack", "O'Neill"], ["Daniel", "Jackson"], ["Samantha", "Carter"]]
```

#### Executes a query and returns the header, that is, the field names and the number of rows the query would produce.
```lisp
(db:header `SELECT name, last_name FROM users WHERE status=?` "active")
; {"count": 3, "fields":["name", "last_name"]}
```

#### Executes a query and returns a reader object from which rows can be read incrementally or all at once.
```lisp
(set reader (db:reader `SELECT name FROM super_users WHERE status=?` "active"))
(echo (reader.fields))
; ["name"]

(while (reader.fetch)
    (echo "row #" (+ 1 (reader.index)) ": " (reader.data))
)
; row #1: {"name": "Jack"}
; row #2: {"name": "Daniel"}
; row #3: {"name": "Samantha"}

(reader.close)
```

#### Executes a query and returns a boolean indicating success or failure.
```lisp
(db:exec `DELETE FROM users WHERE status=?` "inactive"))
; true
```

#### Executes a row update operation and returns boolean indicating success or failure.
```lisp
(db:update "users" "id=1" (& name "Jack" last_name "O'Neill"))
; true

(db:update "users" (& id 1) (& name "Jack"))
; true
```

#### Executes a row insert operation and returns the ID of the newly inserted row or `null` if the operation failed.
```lisp
(db:insert `users` (& name "Daniel" last_name "Jackson"))
; 3
```

#### Returns a single row matching the specified condition.
```lisp
(db:get "users" "id=1")
; {"id": 1, "name": "Jack", "last_name": "O'Neill"}

(db:get "users" (& id 3))
; {"id": 2, "name": "Samantha", "last_name": "Carter"}
```

#### Deletes one or more rows from a table and returns a boolean indicating success or failure.
```lisp
(db:delete "users" "user_id=1")
; true

(db:delete "users" (& user_id 3))
; true
```

#### Returns the ID of the row created by the last insert operation.
```lisp
(db:lastInsertId)
; 3
```

#### Returns the number of affected rows by the last update operation.
```lisp
(db:affectedRows)
; 45
```

#### Opens a new connection and returns the database handle, use it only when managing multiple connections to different
database servers because if only one is used (the default one) this is not necessary.
```lisp
(db:open (& server "localhost" user "main" password "mypwd" database "test" driver "mysql" trace false ))
; [Rose\Data\Connection]
```

#### Closes the specified connection. If the provided connection is the currently active one, it will be closed and the
default connection will be activated (if any).

#### Sets or returns the active conection. Should be used only if you're managing multiple connections.
<br/>Pass `null` as parameter to use the default connection.

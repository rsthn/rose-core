[&laquo; Go Back](./README.md)
# Map


### (`map:new` [key value...])<br/>(`&` [key value...])<br/>{ key value... }
Constructs a map with the given key-value pairs. Note that the second form (&) is legacy from previous syntax.
```lisp
(map:new 'a' 1 'b' 2)
; {"a":1,"b":2}

(& "name" "Jenny" "age" 25)
; {"name":"Jenny","age":25}

{ name "Jon" age 36 }
; {"name":"Jon","age":36}
```

### (`map:sort-asc` \<map>)
Sorts the map in place by value in ascending order.
```lisp
(map:sort-asc (map:new 'b' 2 'a' 1))
; {'a': 1, 'b': 2}
```

### (`map:sort-desc` \<map>)
Sorts the map in place by value in descending order.
```lisp
(map:sort-desc (map:new 'b' 2 'a' 1))
; {'b': 2, 'a': 1}
```

### (`map:ksort-asc` \<map>)
Sorts the map in place by key in ascending order.
```lisp
(map:ksort-asc (map:new 'b' 5 'a' 10))
; {'a': 10, 'b': 5}
```

### (`map:ksort-desc` \<map>)
Sorts the map in place by key in descending order.
```lisp
(map:ksort-desc (map:new 'b' 5 'a' 10))
; {'b': 5, 'a': 10}
```

### (`map:keys` \<map>)
Returns the keys of the map.
```lisp
(map:keys (map:new 'a' 1 'b' 2))
; ['a', 'b']
```

### (`map:values` \<map>)
Returns the values of the map.
```lisp
(map:values (map:new 'a' 1 'b' 2))
; [1, 2]
```

### (`map:set` \<map> [key value...])
Sets one or more key-value pairs in the map.
```lisp
(map:set (map:new 'a' 1) 'b' 2 'x' 15)
; {'a': 1, 'b': 2, 'x': 15}
```

### (`map:get` \<map> \<key>)
Returns the value of the given key in the map.
```lisp
(map:get (map:new 'a' 1 'b' 2) 'b')
; 2
```

### (`map:has` \<map> \<key>)
Returns `true` if the map has the given key, `false` otherwise.
```lisp
(map:has (map:new 'a' 1 'b' 2) 'b')
; true
```

### (`map:del` \<map> \<key>)
Removes the given key from the map and returns the removed value.
```lisp
(map:del (map:new 'a' 1 'b' 112) 'b')
; 112
```

### (`map:key` \<map> \<value>)
Returns the key of the element whose value matches or `null` if not found.
```lisp
(map:key (map:new 'a' 1 'b' 2) 2)
; 'b'
```

### (`map:len` \<map>)
Returns the length of the Map.
```lisp
(map:length (map:new 'a' 1 'b' 2))
; 2
```

### (`map:assign` \<output-map> \<map...>)
Merges one or more maps into the first.
```lisp
(map:assign (map:new 'a' 1) (map:new 'b' 2) (map:new 'c' 3))
; {'a': 1, 'b': 2, 'c': 3}
```

### (`map:merge` \<map...>)
Merges one or more maps into a new map.
```lisp
(map:merge (map:new 'a' 1) (map:new 'b' 2) (map:new 'c' 3))
; {'a': 1, 'b': 2, 'c': 3}
```

### (`map:clear` \<map>)
Clears the contents of the map.
```lisp
(map:clear (map:new 'a' 1 'b' 2))
; {}
```

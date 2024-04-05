[&laquo; Go Back](./Expr.md)
# Array


#### (`array` [values...])
Create a new array with the given values (optional).
```lisp
(array 1 2 3)
; [1, 2, 3]
```

#### (`array:sort` \<var-a> \<var-b> \<array> \<block>)
Sorts the array in place using a custom comparison function.
```lisp
(array:sort a b (array 3 1 2) (- (a) (b)) )
; [1, 2, 3]
```

#### (`array:sort-asc` \<array>)
Sorts the array in place in ascending order.
```lisp
(array:sort-asc (array 3 1 2))
; [1, 2, 3]
```

#### (`array:sort-desc` \<array>)
Sorts the array in place in descending order.
```lisp
(array:sort-desc (array 3 1 2 15 -6 7))
; [15, 7, 3, 2, 1, -6]
```

#### (`array:lsort-asc` \<array>)
Sorts the array in place by the length of its elements in ascending order.
```lisp
(array:lsort-asc (array "fooo" "barsss" "baz" "qx"))
; ["qx", "baz", "fooo", "barsss"]
```

#### (`array:lsort-desc` \<array>)
Sorts the array in place by the length of its elements in descending order.
```lisp
(array:lsort-desc (array "fooo" "barsss" "baz" "qx"))
; ["barsss", "fooo", "baz", "qx"]
```

#### (`array:push` \<array> \<value...>)
Adds one or more values to the end of the array.
```lisp
(array:push (array 1 2) 3 4)
; [1, 2, 3, 4]
```

#### (`array:unshift` \<array> \<value...>)
Adds one or more values to the beginning of the array.
```lisp
(array:unshift (array 1 2) 3 4)
; [3, 4, 1, 2]
```

#### (`array:pop` \<array>)
Removes the last element from the array and returns it.
```lisp
(array:pop (array 1 2 3))
; 3
```

#### (`array:shift` \<array>)
Removes the first element from the array and returns it.
```lisp
(array:shift (array 1 2 3))
; 1
```

#### (`array:first` \<array>)
Returns the first element of the array or `null` if the array is empty.
```lisp
(array:first (array 1 2 3))
; 1
```

#### (`array:last` \<array>)
Returns the last element of the array or `null` if the array is empty.
```lisp
(array:last (array 1 2 3))
; 3
```

#### (`array:remove` \<array> \<index>)
Removes the item from the array at a given index and returns it, throws an error if the index is out of bounds.
```lisp
(array:remove (array 1 2 3) 1)
; 2
(array:remove (array 1 2 3) 3)
; Error: Index out of bounds: 3
```

#### (`array:index` \<array> \<value>)
Returns the index of the item whose value matches or `null` if not found.
```lisp
(array:index (array 1 2 3) 2)
; 1
(array:index (array 1 2 3) 4)
; null
```

#### (`array:last-index` \<array> \<value>)
Returns the last index of the item whose value matches or `null` if not found.
```lisp
(array:last-index (array 1 2 3 2) 2)
; 3
```

#### (`array:length` \<array>)
Returns the length of the array.
```lisp
(array:length (array 1 2 3))
; 3
```

#### (`array:append` \<array> \<array>)
Appends the contents of the given arrays, the original array will be modified.
```lisp
(array:append (array 1 2) (array 3 4))
; [1, 2, 3, 4]
```

#### (`array:merge` \<array> \<array>)
Returns a **new** array as the result of merging the given arrays.
```lisp
(array:merge (array 1 2) (array 3 4))
; [1, 2, 3, 4]
```

#### (`array:unique` \<array>)
Removes all duplicate values from the array and returns a new array.
```lisp
(array:unique (array 1 2 2 3 3 3))
; [1, 2, 3]
```

#### (`array:reverse` \<array>)
Returns a new array with the items in reverse order.
```lisp
(array:reverse (array 1 2 3))
; [3, 2, 1]
```

#### (`array:clear` \<array>)
Clears the contents of the array.
```lisp
(array:clear (array 1 2 3))
; []
```

#### (`array:clone` \<array> [deep=false])
Creates and returns a replica of the array.
```lisp
(array:clone (array 1 2 3))
; [1, 2, 3]
```

#### (`array:flatten` [depth] \<array>)
Returns a flattened array up to the specified depth.
```lisp
(array:flatten (array 1 2 (array 3 4) 5))
; [1, 2, 3, 4, 5]

(array:flatten 1 (array 1 2 (array 3 (array 4 5 6)) 7))
; [1, 2, 3, [4, 5, 6], 7]
```

#### (`array:slice` \<start> [length] \<array>)
Returns a slice of the array, starting at the given index and reading the specified number of items,
if the length is not specified the rest of items after the index (inclusive) will be returned.
```lisp
(array:slice 1 2 (array 1 2 3 4 5))
; [2, 3]

(array:slice 2 (array 1 2 3 4 5))
; [3, 4, 5]

(array:slice -3 2 (array 1 2 3 4 5))
; [3, 4]

(array:slice 1 -1 (array 1 2 3 4 5))
; [2, 3, 4]
```

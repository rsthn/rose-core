[&laquo; Go Back](./Expr.md)
# Image


#### Loads an image from a file.

#### Saves the image to the given path. If no target specified the filename used in `load` will be used.

#### Outputs the image to the browser. This method will send header information, therefore it is required
that no data had been output when called.

#### Returns the binary image data. The following output modes are supported: `BINARY`, `DATA_URI`, and `BASE64`.

#### Returns or sets the width of the image. If the `newWidth` parameter is not `null` the image will be horizontally
resized to the given width, and the height will be adjusted if the `keepRatio` parameter is set to true.

#### Returns or sets the height of the image. If the `newHeight` parameter is not `null` the image will be vertically
resized to the given height, and the width will be adjusted if the `keepRatio` parameter is set to true.

#### Resizes the image to the given width and height.
<br/>NOTE: Operates on the same image by default, set `rewriteOriginal` to `false` to prevent this.

#### Scales the image by the given the width and height factors. Each factor must be a real number between 0 and 1 (inclusive),
if parameter `scaleY` is not set, the same value as `scaleX` will be used.
<br/>NOTE: Operates on the same image by default, set `rewriteOriginal` to `false` to prevent this.

#### Resizes the image to fit in the given area, maintains the aspect ratio.
<br/>NOTE: Operates on the same image by default, set `rewriteOriginal` to `false` to prevent this.

#### Cuts a portion of the image and returns a new image object. Set the start coordinates to `null` to get a
centered cut. If the size parameters are `null` they will be set to the size of the image.

#### Crops to a portion of the image. This is similar to cutting a portion of the image and replacing the original
by the portion.

#### Cuts a portion of the image smartly by detecting if scaling is needed and scale accordingly. The `onTooWide` and `onTooTall`
parameters indicate how to cut if the image is either too wide or too tall respectively. Valid values for these parameters are
"CENTER", "LEFT", "RIGHT", "TOP", and "BOTTOM".
<br/>
<br/>NOTE: Operates on the same image by default, set `rewriteOriginal` to `false` to prevent this.

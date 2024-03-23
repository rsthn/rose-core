[&laquo; Go Back](./Expr.md)
# Image

## (`image:load` \<path>)
Loads an image from a file.

## (`image:save` \<image> [path=null] [format=JPG|GIF|PNG] [quality=95])
Saves the image to the given path. If no target specified the filename used in `load` will be used.

## (`image:dump` \<image> [format=JPG|GIF|PNG] [quality=95])
Outputs the image to the browser. This method will send header information, therefore it is required
that no data had been output when called.

## (`image:data` \<image> [format=JPG|GIF|PNG] [mode=BINARY|DATA_URI|BASE64] [quality=95])
Returns the binary image data. The following output modes are supported: `BINARY`, `DATA_URI`, and `BASE64`.

## (`image:width` \<image> \<newWidth> [keepRatio=true])<br/>(`image:width` \<image>)
Returns or sets the width of the image. If the `newWidth` parameter is not `null` the image will be horizontally
resized to the given width, and the height will be adjusted if the `keepRatio` parameter is set to true.

## (`image:height` \<image> \<newHeight> [keepRatio=true])<br/>(`image:height` \<image>)
Returns or sets the height of the image. If the `newHeight` parameter is not `null` the image will be vertically
resized to the given height, and the width will be adjusted if the `keepRatio` parameter is set to true.

## (`image:resize` \<image> \<newWidth> \<newHeight> [rewriteOriginal=true])
Resizes the image to the given width and height.
<br/>NOTE: Operates on the same image by default, set `rewriteOriginal` to `false` to prevent this.

## (`image:scale` \<image> \<scaleX> [scaleY=null] [rewriteOriginal=true])
Scales the image by the given the width and height factors. Each factor must be a real number between 0 and 1 (inclusive),
if parameter `scaleY` is not set, the same value as `scaleX` will be used.
<br/>NOTE: Operates on the same image by default, set `rewriteOriginal` to `false` to prevent this.

## (`image:fit` \<image> \<newWidth> \<newHeight> [rewriteOriginal=false])
Resizes the image to fit in the given area, maintains the aspect ratio.
<br/>NOTE: Operates on the same image by default, set `rewriteOriginal` to `false` to prevent this.

## (`image:cut` \<image> \<newWidth> \<newHeight> [startX] [startY])
Cuts a portion of the image and returns a new image object. Set the start coordinates to `null` to get a
centered cut. If the size parameters are `null` they will be set to the size of the image.

## (`image:crop` \<image> \<newWidth> \<newHeight> [startX] [startY])
Crops to a portion of the image. This is similar to cutting a portion of the image and replacing the original
by the portion.

## (`image:smartcut` \<image> \<newWidth> \<newHeight> \<onTooWide> \<onTooTall> [rewriteOriginal=false])
Cuts a portion of the image smartly by detecting if scaling is needed and scale accordingly. The `onTooWide` and `onTooTall`
parameters indicate how to cut if the image is either too wide or too tall respectively. Valid values for these parameters are
"CENTER", "LEFT", "RIGHT", "TOP", and "BOTTOM".
<br/>
<br/>NOTE: Operates on the same image by default, set `rewriteOriginal` to `false` to prevent this.

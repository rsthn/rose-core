<?php

namespace Rose\Ext;

use Rose\Errors\ArgumentError;
use Rose\Expr;
use Rose\Image;

function getImage ($args) {
    $img = $args->get(1);
    if (\Rose\typeOf($img) != 'Rose\\Image') throw new ArgumentError('Image object was expected');
    return $img;
}

// @title Image

/**
 * Loads an image from a file.
 * @code (`image:load` <path>)
 */
Expr::register('image:load', function ($args) {
    return new Image ($args->get(1));
});

/**
 * Saves the image to the given path. If no target specified the filename used in `load` will be used.
 * @code (`image:save` <image> [path=null] [format=JPG|GIF|PNG] [quality=95])
 */
Expr::register('image:save', function ($args) {
    getImage($args)->save($args->{2}, $args->{3}, $args->{4} || 95);
    return null;
});

/**
 * Outputs the image to the browser. This method will send header information, therefore it is required
 * that no data had been output when called.
 * @code (`image:dump` <image> [format=JPG|GIF|PNG] [quality=95])
 */
Expr::register('image:dump', function ($args) {
    getImage($args)->output($args->{2}, $args->{3} || 95);
    return null;
});

/**
 * Returns the binary image data. The following output modes are supported: `BINARY`, `DATA_URI`, and `BASE64`.
 * @code (`image:data` <image> [format=JPG|GIF|PNG] [mode=BINARY|DATA_URI|BASE64] [quality=95])
 */
Expr::register('image:data', function ($args) {
    return getImage($args)->data($args->{2}, $args->{3} || 'BINARY', $args->{4} || 95);
});

/**
 * Returns or sets the width of the image. If the `newWidth` parameter is not `null` the image will be horizontally
 * resized to the given width, and the height will be adjusted if the `keepRatio` parameter is set to true.
 * @code (`image:width` <image> <newWidth> [keepRatio=true])
 * @code (`image:width` <image>)
 */
Expr::register('image:width', function ($args) {
    return getImage($args)->width($args->{2}, $args->{3} === 'false' ? false : true);
});

/**
 * Returns or sets the height of the image. If the `newHeight` parameter is not `null` the image will be vertically
 * resized to the given height, and the width will be adjusted if the `keepRatio` parameter is set to true.
 * @code (`image:height` <image> <newHeight> [keepRatio=true])
 * @code (`image:height` <image>)
 */
Expr::register('image:height', function ($args) {
    return getImage($args)->height($args->{2}, $args->{3} === 'false' ? false : true);
});

/**
 * Resizes the image to the given width and height.
 * NOTE: Operates on the same image by default, set `rewriteOriginal` to `false` to prevent this.
 * @code (`image:resize` <image> <newWidth> <newHeight> [rewriteOriginal=true])
 */
Expr::register('image:resize', function ($args) {
    return getImage($args)->resize($args->get(2), $args->get(3), $args->{4} === 'false' ? false : true);
});

/**
 * Scales the image by the given the width and height factors. Each factor must be a real number between 0 and 1 (inclusive),
 * if parameter `scaleY` is not set, the same value as `scaleX` will be used.
 * NOTE: Operates on the same image by default, set `rewriteOriginal` to `false` to prevent this.
 * @code (`image:scale` <image> <scaleX> [scaleY=null] [rewriteOriginal=true])
 */
Expr::register('image:scale', function ($args) {
    return getImage($args)->scale($args->get(2), $args->{3}, $args->{4} === 'false' ? false : true);
});

/**
 * Resizes the image to fit in the given area, maintains the aspect ratio.
 * NOTE: Operates on the same image by default, set `rewriteOriginal` to `false` to prevent this.
 * @code (`image:fit` <image> <newWidth> <newHeight> [rewriteOriginal=false])
 */
Expr::register('image:fit', function ($args) {
    return getImage($args)->fit($args->get(2), $args->get(3), $args->{4} === 'true' ? true : false);
});

/**
 * Cuts a portion of the image and returns a new image object. Set the start coordinates to `null` to get a
 * centered cut. If the size parameters are `null` they will be set to the size of the image.
 * @code (`image:cut` <image> <newWidth> <newHeight> [startX] [startY])
 */
Expr::register('image:cut', function ($args) {
    return getImage($args)->cut($args->get(2), $args->get(3), $args->{4}, $args->{5});
});

/**
 * Crops to a portion of the image. This is similar to cutting a portion of the image and replacing the original
 * by the portion.
 * @code (`image:crop` <image> <newWidth> <newHeight> [startX] [startY])
 */
Expr::register('image:crop', function ($args) {
    return getImage($args)->crop($args->get(2), $args->get(3), $args->{4}, $args->{5});
});

/**
 * Cuts a portion of the image smartly by detecting if scaling is needed and scale accordingly. The `onTooWide` and `onTooTall`
 * parameters indicate how to cut if the image is either too wide or too tall respectively. Valid values for these parameters are
 * "CENTER", "LEFT", "RIGHT", "TOP", and "BOTTOM".
 *
 * NOTE: Operates on the same image by default, set `rewriteOriginal` to `false` to prevent this.
 * @code (`image:smartcut` <image> <newWidth> <newHeight> <onTooWide> <onTooTall> [rewriteOriginal=false])
 */
Expr::register('image:smartcut', function ($args) {

    return getImage($args)->smartCut($args->get(2), $args->get(3), Image::getCodeForPosition($args->get(4)), Image::getCodeForPosition($args->get(5)), $args->{6} === 'true' ? true : false);
});

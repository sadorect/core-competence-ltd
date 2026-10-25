# Gallery images

Photos shown on `/gallery`. The `*.svg` files here are **placeholders** — replace them with
real facility/project photos.

## Adding a real photo

1. Drop the image file in this folder. Recommended: **JPEG, ~1600 px on the longest edge,
   under ~300 KB**, landscape 4:3 works best (tiles are cropped to 4:3 with `object-fit: cover`).
2. Add a tile to the `<ul class="gallery-grid">` in `gallery.html`, following the existing pattern:

   ```html
   <li class="gallery-item" data-category="aviation">
     <button type="button" class="gallery-item__btn"
             data-full="/assets/img/gallery/your-photo.jpg"
             data-caption="Short caption shown in the lightbox">
       <img src="/assets/img/gallery/your-photo.jpg" width="800" height="600"
            loading="lazy" alt="Meaningful description of the photo">
       <span class="gallery-item__caption">Short tile caption</span>
     </button>
   </li>
   ```

3. `data-category` must be one of (drives the filter buttons):
   `aviation` · `maritime` · `logistics` · `industrial` · `commercial`
4. Always set a descriptive `alt`, and `width`/`height` (any real ratio — CSS crops to 4:3) so the
   layout doesn't jump while images load.

No build step: just edit `gallery.html` and deploy.

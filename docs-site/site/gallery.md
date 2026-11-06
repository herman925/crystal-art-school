# The gallery

**Artwork** in the dashboard menu. One entry per painting.

| Field | Notes |
|---|---|
| **Title** | The painting's name |
| **Featured image** | The photograph |
| **Content** | The description shown when a visitor opens it |
| **Price (£)** | Under Sale details |
| **Etsy listing link** | Under Sale details |
| **Availability** | Under Sale details |
| **Artwork Media** | In the sidebar; separate multiple media with commas |
| **Size** | Under Sale details |
| **Artwork Types** | Optional tags in the sidebar; separate values with commas |
| **Order** | Breaks ties between paintings with the same publication date; also controls the homepage card order |

Type is optional,
not a publishing requirement; the Type filter appears only when at least two distinct
real Type values exist. Leave unknown Types empty rather than making them up.

The Gallery's **Newest** and **Oldest** choices sort by publication date, newest first or
oldest first respectively. For paintings with the same publication date, lower Order numbers
come first.

Reuse the sidebar’s existing media/type suggestions to keep spellings consistent. Ask the site administrator
if a label needs renaming across many entries. The artwork list
also has **Newest First** and **Oldest First** buttons for publication-date sorting.

## The Etsy link is what makes it buyable

<figure class="guide-screenshot" aria-labelledby="artwork-sale-visual">
  <figcaption id="artwork-sale-visual">Artwork: the actual sale-details and save controls</figcaption>
  <div class="guide-screenshot-images">
    <a href="/guide-screenshots/artwork-sale.png" target="_blank" rel="noopener" style="--screenshot-width:480px" aria-label="Open full-size screenshot: Artwork: actual Price (£), Etsy listing link and Availability fields (new tab)"><img src="/guide-screenshots/artwork-sale.png" width="480" height="437" alt="Artwork: actual Price (£), Etsy listing link and Availability fields" loading="lazy"></a>
    <a href="/guide-screenshots/artwork-save.png" target="_blank" rel="noopener" style="--screenshot-width:183px" aria-label="Open full-size screenshot: WordPress: actual Save control for this entry (new tab)"><img src="/guide-screenshots/artwork-save.png" width="183" height="32" alt="WordPress: actual Save control for this entry" loading="lazy"></a>
  </div>
  <p>Use the painting’s exact Etsy listing when it is for sale. Save a published entry with Save; publish a new entry when it is ready. Select a screenshot to open it full size.</p>
</figure>

Selling happens on Etsy, not on this site. The **Buy** button links straight to that
painting's Etsy listing.

If the Etsy URL is empty, **no Buy button appears at all**. That is deliberate — it is
better than a button that goes to a search page and leaves the visitor to hunt. So a
painting with no link is a painting on display, not for sale.

## If the gallery is empty

Until artwork is published, visitors see the page's introduction and its empty-gallery message. Check the current list instead of assuming photographs alone are enough to publish a painting.

Check these before publishing a painting:

1. Its name.
2. The agreed price and offer details, if it is for sale.
3. The medium, such as watercolour or oil. Type is optional and is not required to publish.
4. Its Etsy address, or a decision that it is on display rather than for sale.

Fill in the agreed details, publish the intended entry, then check its public card and Etsy link.

<figure class="guide-screenshot" aria-labelledby="artwork-editor-current-visual">
  <figcaption id="artwork-editor-current-visual">The actual artwork editor</figcaption>
  <div class="guide-screenshot-images">
    <a href="/guide-screenshots/artwork-editor-overview.png" target="_blank" rel="noopener" style="--screenshot-width:1440px" aria-label="Open full-size screenshot: WordPress: actual new artwork editor; blank auto-draft, no content published (new tab)"><img src="/guide-screenshots/artwork-editor-overview.png" width="1440" height="1100" alt="WordPress: actual new artwork editor; blank auto-draft, no content published" loading="lazy"></a>
  </div>
  <p>A blank new entry is shown. Prepare the picture and agreed sale/display details, then use the entry’s own save or publication control. Select a screenshot to open it full size.</p>
</figure>

## Photographs

Shoot square-on in even light, and crop to the painting's edges. The grid crops what you
upload to fit, so a photo with a lot of table around it loses the painting.

## The homepage gallery card

<figure class="guide-screenshot" aria-labelledby="home-artwork-visual">
  <figcaption id="home-artwork-visual">Home: the actual artwork picker</figcaption>
  <div class="guide-screenshot-images">
    <a href="/guide-screenshots/home-gallery-picker.png" target="_blank" rel="noopener" style="--screenshot-width:850px" aria-label="Open full-size screenshot: Home: actual artwork picker with its separate Save Gallery Image button (new tab)"><img src="/guide-screenshots/home-gallery-picker.png" width="850" height="624" alt="Home: actual artwork picker with its separate Save Gallery Image button" loading="lazy"></a>
  </div>
  <p>Save Gallery Image saves this choice separately from the rest of the Home form. Select a screenshot to open it full size.</p>
</figure>

Choose its picture through **Content Hub → Home → Edit Home → Gallery Image → Choose Artwork**.
Select a published artwork and use **Save Gallery Image**. This saves separately from the
rest of the Home form. The card follows that artwork's Featured Image; if it is withdrawn
or loses its public picture, Home falls back to another published artwork.

Before a selection is saved, artwork Order supplies the picture. The Gallery page's own
Newest/Oldest sorting still uses publication dates.
Until one painting is published, that card does not appear on the homepage at all.


## Longer Medium lists

Up to six media appear as buttons. When more are in use, **Choose Medium** opens a
searchable picker. Gallery allows several selected media; any selected medium can match.
Use the selected label’s remove button to deselect it, or **Clear selection** to clear
Medium only. **Done** returns to the Gallery; the page’s **Clear all** resets all filters.
On a phone, open **Filter artwork** first. The picker uses existing media and does not
create new labels. With JavaScript unavailable, the full list remains visible.

x13allegro — EAN Category Matcher (PrestaShop 8.2.1)

WHAT'S INSIDE
-------------
1) controllers/admin/AdminXAllegroCategorySuggestController.php
   AJAX controller returning JSON with Allegro category suggestions.
   Call with: index.php?controller=AdminXAllegroCategorySuggest&ajax=1&action=probe&token=...

2) classes/php81/Service/EanCategoryProbeService.php
   Allegro API client for reading categories by EAN (and by phrase as fallback).

3) classes/php81/Service/CategoryMatcherService.php
   High-level service that:
     - collects EANs from a Presta category (or by search),
     - queries Allegro,
     - aggregates category counts and resolves readable category paths.

4) views/templates/admin/category_ean_matcher.tpl
   UI snippet with "Użyj EAN produktów", "Ilość EAN", and "ZAPROPONUJ" button.
   Renders summary + per-EAN examples and emits an event when a category is chosen.

5) views/templates/admin/_inject_endpoint.tpl
   Small snippet that injects the AJAX endpoint with a valid admin token. Include it once on the page.

INSTALL
-------
- Drop the files into your module directory: modules/x13allegro/...
- Clear cache.
- On the "Powiązania kategorii" page, include the two templates:
    {include file="modules/x13allegro/views/templates/admin/_inject_endpoint.tpl"}
    {include file="modules/x13allegro/views/templates/admin/category_ean_matcher.tpl"}
- Make sure your module is authenticated with Allegro (has an access token).
- The UI will take the selected Presta category from the tree, fetch up to N EANs,
  probe Allegro, and show the most frequent categories with paths (Parent > Child > Leaf).

NOTES
-----
- The controller uses ajaxProcessProbe() (native Presta pattern).
- EAN scan uses both product.ean13 and product_attribute.ean13.
- API calls prefer /sale/products?ean=... and fall back to /offers/listing?phrase=EAN.
- Category paths are built via /sale/categories/{id}.

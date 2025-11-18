/**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2025 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */
var storage;
var langId = document.getElementsByTagName("html")[0].getAttribute("lang");
var currentFeatureModule = tvthemename + "_feature_" + langId;
jQuery(document).ready(function($) {
    storage = $.localStorage;

    function storageGet(key) {
        return "" + storage.get(currentFeatureModule + key);
    }

    function storageSet(key, value) {
        storage.set(currentFeatureModule + key, value);
    }
    function storageClear(key) {
        storage.remove(currentFeatureModule + key);
    }
    var gettvcmsfeatureproductsajax = function() {
        if ($('.tvcmsfeatured-product').length) {
            /*****Load Cache*****/
            var data = storageGet("");
            if (data != "null") {
                $('.tvcmsfeatured-product').html(data);
                makeFeatureProductSlider();
                productTime(); //custom.js
            }
            /*****Load Cache*****/
            $.ajax({
                type: 'POST',
                url: gettvcmsfeaturedproductslink,
                success: function(data) {
                    var dataCache = storageGet("");
                    storageSet("", data);
                    if (dataCache === 'null') {
                        $('.tvcmsfeatured-product').html(data);
                        makeFeatureProductSlider();
                        customImgLazyLoad('.tvcmsfeatured-product');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    //setTimeout(function(){gettvcmsfeatureproductsajax()},500);
                    console.log(textStatus, errorThrown);
                }
            });
        }
    }
    //setTimeout(function(){gettvcmsfeatureproductsajax()},500);

    themevoltyCallEventsPush(gettvcmsfeatureproductsajax, null);

    function makeFeatureProductSlider() {
        var swiperClass = [
            //['slider className','navigation nextClass','navigation prevClass','paging className']
            ['.tvcmsfeatured-product .tvfeatured-product-wrapper', '.tvcmsfeatured-next', '.tvcmsfeatured-prev', '.tvcmsfeatured-product'],
        ];
        for (var i = 0; i < swiperClass.length; i++) {
            if ($(swiperClass[i][0]).attr('data-has-image') == 'true') {
                $(swiperClass[i][0]).owlCarousel({
                    loop: false,
                    dots: false,
                    nav: false,
                    smartSpeed: tvMainSmartSpeed,
                    responsive: {
                        0: { items: 1 },
                        320: { items: 1, slideBy: 1 },
                        330: { items: 2, slideBy: 1 },
                        400: { items: 2, slideBy: 1 },
                        480: { items: 2, slideBy: 1 },
                        650: { items: 2, slideBy: 1 },
                        767: { items: 2, slideBy: 1 },
                        768: { items: 2, slideBy: 1 },
                        992: { items: 3, slideBy: 1 },
                        1023: { items: 3, slideBy: 1 },
                        1024: { items: 3, slideBy: 1 },
                        /* --- START MODYFIKACJI --- */
                        1200: { items: 5, slideBy: 1 }, /* BYŁO 3 */
                        1350: { items: 5, slideBy: 1 }, /* BYŁO 3 */
                        1660: { items: 5, slideBy: 1 }, /* BYŁO 4 */
                        1800: { items: 5, slideBy: 1 }  /* BYŁO 4 */
                        /* --- KONIEC MODYFIKACJI --- */
                    }
                });
            } else {
                $(swiperClass[i][0]).owlCarousel({
                    loop: false,
                    dots: false,
                    nav: false,
                    smartSpeed: tvMainSmartSpeed,
                    responsive: {
                        0: { items: 1 },
                        320: { items: 1, slideBy: 1 },
                        330: { items: 2, slideBy: 1 },
                        400: { items: 2, slideBy: 1 },
                        480: { items: 2, slideBy: 1 },
                        650: { items: 3, slideBy: 1 },
                        767: { items: 3, slideBy: 1 },
                        768: { items: 3, slideBy: 1 },
                        992: { items: 4, slideBy: 1 },
                        1023: { items: 4, slideBy: 1 },
                        1024: { items: 4, slideBy: 1 },
                        /* --- START MODYFIKACJI --- */
                        1200: { items: 5, slideBy: 1 }, /* BYŁO 4 */
                        1350: { items: 5, slideBy: 1 }, /* BYŁO 4 */
                        1660: { items: 5, slideBy: 1 },
                        1800: { items: 5, slideBy: 1 }
                        /* --- KONIEC MODYFIKACJI --- */
                    }
                });
            }
            $(swiperClass[i][1]).on('click', function(e) {
                e.preventDefault();
                $('.' + $(this).attr('data-parent') + ' .owl-nav .owl-next').trigger('click');
            });
            $(swiperClass[i][2]).on('click', function(e) {
                e.preventDefault();
                $('.' + $(this).attr('data-parent') + ' .owl-nav .owl-prev').trigger('click');
            });
            $(swiperClass[i][3] + ' .tv-pagination-wrapper').insertAfter(swiperClass[i][3] + ' .tvcmsmain-title-wrapper');
        }
    }
});
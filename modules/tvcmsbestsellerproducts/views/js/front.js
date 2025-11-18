/**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
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
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

var storage;
var langId = document.getElementsByTagName("html")[0].getAttribute("lang");
var currentBestModule = tvthemename + "_best_" + langId;
jQuery(document).ready(function($) {
    storage = $.localStorage;

    function storageGet(key) {
        return "" + storage.get(currentBestModule + key);
    }

    function storageSet(key, value) {
        storage.set(currentBestModule + key, value);
    }
    function storageClear(key) {
        storage.remove(currentBestModule + key);
    }
    var gettvcmsbestsellerproductsajax = function() {
        if ($('.tvcmsbest-seller-product').length) {
            /*****Load Cache*****/
            var data = storageGet("");
            if (data != "null") {
                $('.tvcmsbest-seller-product').html(data);
                makeBestProductSlider();
                productTime(); //custom.js
            }
            /*****Load Cache*****/
            $.ajax({
                type: 'POST',
                url: gettvcmsbestsellerproductslink,
                success: function(data) {
                    var dataCache = storageGet("");
                    storageSet("", data);
                    if (dataCache === 'null') {
                        $('.tvcmsbest-seller-product').html(data);
                        makeBestProductSlider();
                        customImgLazyLoad('.tvcmsbest-seller-product');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    //setTimeout(function(){gettvcmsbestsellerproductsajax()},500);
                    console.log(textStatus, errorThrown);
                }
            });
        }
    }
    //setTimeout(function(){gettvcmsbestsellerproductsajax()},500); 
    themevoltyCallEventsPush(gettvcmsbestsellerproductsajax, null);
    function makeBestProductSlider() {
        var swiperClass = [
            //['slider className','navigation nextClass','navigation prevClass','paging className']
            ['.tvcmsbest-seller-product .tvbest-seller-product-wrapper', '.tvcmsbest-seller-next', '.tvcmsbest-seller-prev', '.tvcmsbest-seller-product'],
        ];

        for (var i = 0; i < swiperClass.length; i++) {
            if ($(swiperClass[i][0]).attr('data-has-image') == 'true') {
                $(swiperClass[i][0]).owlCarousel({
                    loop: false,
                    dots: false,
                    smartSpeed: 400,
                    smartSpeed: tvMainSmartSpeed,
                    nav: false,
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
                    smartSpeed: 400,
                    smartSpeed: tvMainSmartSpeed,
                    nav: false,
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
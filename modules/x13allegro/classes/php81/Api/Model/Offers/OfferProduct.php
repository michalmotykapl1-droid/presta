<?php

namespace x13allegro\Api\Model\Offers;

use JsonSerializable;

class OfferProduct implements JsonSerializable
{
    /** @var string */
    public $id;

    /** @var string */
    public $language;

    /** @var string */
    public $name;

    /** @var External */
    public $external;

    /** @var Description */
    public $description;

    /** @var Category */
    public $category;

    /** @var ProductSet[] */
    public $productSet;

    /** @var array */
    public $parameters;

    /** @var array */
    public $images;

    /** @var Publication */
    public $publication;

    /** @var SellingMode */
    public $sellingMode;

    /** @var TaxSettings */
    public $taxSettings;

    /** @var SizeTable */
    public $sizeTable;

    /** @var Stock */
    public $stock;

    /** @var Delivery */
    public $delivery;

    /** @var Discounts */
    public $discounts;

    /** @var Location */
    public $location;

    /** @var Payments */
    public $payments;

    /** @var AdditionalMarketplaces */
    public $additionalMarketplaces;

    /** @var AfterSalesServices */
    public $afterSalesServices;

    /** @var AdditionalServices */
    public $additionalServices;

    /** @var MessageToSellerSettings */
    public $messageToSellerSettings;

    /** @var B2b */
    public $b2b;

    /**
     * @param string $url
     * @return $this
     */
    public function image($url)
    {
        $this->images[] = $url;

        return $this;
    }

    /**
     * @param ProductSet $productSet
     * @return $this
     */
    public function productSet(ProductSet $productSet)
    {
        $this->productSet = [$productSet];

        return $this;
    }

    /**
     * @deprecated
     * @param ProductSet\Product $product
     * @return $this
     */
    public function setProduct(ProductSet\Product $product)
    {
        $productSet = new ProductSet();
        $productSet->product = $product;

        $this->productSet = [$productSet];

        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'language' => $this->language,
            'name' => $this->name,
            'external' => $this->external,
            'description' => $this->description,
            'parameters' => $this->parameters,
            'category' => $this->category,
            'productSet' => $this->productSet,
            'images' => $this->images,
            'publication' => $this->publication,
            'sellingMode' => $this->sellingMode,
            'taxSettings' => $this->taxSettings,
            'sizeTable' => $this->sizeTable,
            'stock' => $this->stock,
            'delivery' => $this->delivery,
            'discounts' => $this->discounts,
            'location' => $this->location,
            'payments' => $this->payments,
            'additionalMarketplaces' => $this->additionalMarketplaces,
            'afterSalesServices' => $this->afterSalesServices,
            'additionalServices' => (is_object($this->additionalServices) && $this->additionalServices->id ? $this->additionalServices : null),
            'messageToSellerSettings' => $this->messageToSellerSettings,
            'b2b' => $this->b2b
        ];
    }
}

<?php
declare(strict_types=1);

namespace CodeCave\EAN\Test\Integration\Support;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Checkout\Test\Fixture\SetBillingAddress;
use Magento\Checkout\Test\Fixture\SetDeliveryMethod;
use Magento\Checkout\Test\Fixture\SetGuestEmail;
use Magento\Checkout\Test\Fixture\SetShippingAddress;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory as RegionCollectionFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\GuestCart;
use Magento\TestFramework\Helper\Bootstrap;

class CartBuilder
{
    public const PREFIX = 'ean102it-';

    public static function guestCart(string $countryId): int
    {
        $om = Bootstrap::getObjectManager();
        $product = $om->get(ProductFixture::class)->apply([
            'sku' => self::PREFIX . '%uniqid%',
            'name' => 'EAN integration %uniqid%',
            'price' => 100,
        ]);
        $cartId = (int) $om->get(GuestCart::class)->apply()->getId();
        $om->get(AddProductToCart::class)->apply(
            ['cart_id' => $cartId, 'product_id' => $product->getId(), 'qty' => 1]
        );
        $address = self::address($countryId);
        $om->get(SetBillingAddress::class)->apply(['cart_id' => $cartId, 'address' => $address]);
        $om->get(SetShippingAddress::class)->apply(['cart_id' => $cartId, 'address' => $address]);
        $om->get(SetGuestEmail::class)->apply(['cart_id' => $cartId, 'email' => self::PREFIX . '%uniqid%@example.com']);
        $om->get(SetDeliveryMethod::class)->apply(['cart_id' => $cartId]);

        return $cartId;
    }

    public static function maskedId(int $cartId): string
    {
        return Bootstrap::getObjectManager()->get(QuoteIdToMaskedQuoteIdInterface::class)->execute($cartId);
    }

    public static function load(int $cartId): Quote
    {
        return Bootstrap::getObjectManager()->create(CartRepositoryInterface::class)->get($cartId);
    }

    private static function address(string $countryId): array
    {
        // DK and SE require a region in Magento's default config
        $region = Bootstrap::getObjectManager()->create(RegionCollectionFactory::class)->create()
            ->addCountryFilter($countryId)
            ->getFirstItem();

        return [
            'country_id' => $countryId,
            'region_id' => $region->getId() ? (int) $region->getId() : null,
            'postcode' => '2100',
            'city' => 'Copenhagen',
            'street' => ['Testvej 1'],
            'company' => self::PREFIX . 'company',
        ];
    }
}

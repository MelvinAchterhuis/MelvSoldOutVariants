<?php declare(strict_types=1);

namespace Melv\SoldOutVariants\Subscriber;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

class ProductLoadedSubscriber implements EventSubscriberInterface
{
    /** @var ProductEntity */
    protected $productRepository;

    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageLoadedEvent::class=> 'onProductLoaded'
        ];
    }

    public function __construct(
        EntityRepository $productRepository
    )
    {
        $this->productRepository = $productRepository;
    }

    public function onProductLoaded(ProductPageLoadedEvent $event)
    {
        $currentProduct = $event->getPage()->getProduct();
        $variationCount = count($currentProduct->getVariation());

        /** Only get variant stock and add extension when there is 1 variation possible */
        if ($variationCount !== 1) {
            return;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('parentId', $currentProduct->getParentId()));

        $variants = $this->productRepository->search(
            $criteria,
            Context::createDefaultContext()
        )->getEntities();

        $event->getPage()->addExtension('MelvSoldOutVariants', $variants);
    }
}

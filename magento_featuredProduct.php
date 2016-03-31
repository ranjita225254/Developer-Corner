<div class="feautured-section">
    <div class="promo-title text-center">
        <h3>Gimmee Jimmy's Believes in Free Shipping, Always</h3>
    </div>
    <div class="home_product_section row">
        <?php
        $collection = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes());
        $count=0;
        foreach ($collection as $_product) {
            $is_featured = Mage::getResourceModel('catalog/product')->getAttributeRawValue($_product->getEntityId(), 'is_featured', Mage::app()->getStore()->getId());
            if ($is_featured) {
                if($count<=5){
                
                ?>

                <div class="col-sm-4">

                    <div class="home_product">
                        <a href="<?php echo $_product->getProductUrl() ?>">
                            <span><?php echo $_product['name']; ?></span>
                        </a>
                        <?php
                        $productId = $_product->getId();
                        $reviews = Mage::getModel('review/review')
                                ->getResourceCollection()
                                ->addStoreFilter(Mage::app()->getStore()->getId())
                                ->addEntityFilter('product', $productId)
                                ->addStatusFilter(Mage_Review_Model_Review::STATUS_APPROVED)
                                ->setDateOrder()
                                ->addRateVotes();
                        /**
                         * Getting average of ratings/reviews
                         */
                        $avg = 0;
                        $ratings = array();
                        if (count($reviews) > 0) {
                            foreach ($reviews->getItems() as $review) {
                                foreach ($review->getRatingVotes() as $vote) {
                                    $ratings[] = $vote->getPercent();
                                }
                            }
                            $avg = array_sum($ratings) / count($ratings);
                        }
                        ?>

                        <div>
                            <a title="" href="<?php echo $_product->getProductUrl(); ?>">
                                <img width="300" height="237" title="" alt="" src="<?php echo $imageThumbnail = Mage::getModel('catalog/product_media_config')->getMediaUrl($_product->getThumbnail()); ?>">
                            </a>
                        </div> 
                        <div>
                            <span>
                                <span>
                                    <?php echo Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol(); ?>
                                    <?php
                                    $n = $_product['price'];
                                    echo number_format($n, 2, '.', ',');
                                    ?>
                                </span>
                            </span>
                            <div>
                                <?php if ($avg): ?>
                                    <div class="rating-box" style="float:left;">
                                        <div class="rating" style="width: <?php echo ceil($avg); ?>%;"></div>
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                </div>
            <?php }
                $count++;
        }
            ?>
            <?php
        }
        ?>
    </div>
    <div>
        <?php
        echo
        $this->getLayout()->createBlock('cms/block')->setBlockId('home_page_footer_content')->toHtml();
        ?>

    </div>
</div>

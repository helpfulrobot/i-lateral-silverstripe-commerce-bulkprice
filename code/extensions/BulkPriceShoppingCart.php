<?php

/**
 * Extension class responsible for updating the price of items in the
 * shopping cart, based on any bulk discounts set via the admin
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package commerce-bulkprice
 */
class BulkPriceShoppingCart extends Extension {

    /**
     * Calculate the bulk pricing of the item submitted
     * 
     * @param $object (the object the bulk pricing is assigned to
     * @param $item (the item in the shopping cart)
     */
    private function calculate_bulk_price($object, $item) {
        $return = new Currency("Price");
        $price = 0;

        foreach($object->BulkPrices() as $bulk_price) {
            $range = array();

            // Determine whaty type of price we are dealing with
            if(strpos($bulk_price->Quantity,"-") !== false) { // We are looking for a range
                $range = explode("-", $bulk_price->Quantity);
            } elseif(strpos($bulk_price->Quantity,"+") !== false) { // We are looking this number or greater
                $range[0] = str_replace("+", "", $bulk_price->Quantity);
                $range[1] = -1; // -1 means no upper limit
            } else { // Assume we are dealing with a single price
                $range[0] = $bulk_price->Quantity;
                $range[1] = $bulk_price->Quantity;
            }

            // Now cast quantities correctly
            $range[0] = (int)$range[0];
            $range[1] = (int)$range[1];

            // Finally check if the current quantity sits in the
            // current range and amend price
            if(
                ($range[1] == -1 && $item->Quantity >= $range[0]) ||
                ($item->Quantity >= $range[0] && $item->Quantity <= $range[1])
            )
                $price = $bulk_price->Price;
        }
        
        if(!$price)
            $return = $item->Price;
        else
            $return->setValue($price);

        if($item->Customisations) {
            // Check for customisations that modify price
            foreach($item->Customisations as $custom_item) {
                // If a customisation modifies price, adjust the price
                $price += ($custom_item->Price) ? (float)$custom_item->Price : 0;
            }
        }

        // finally, return price
        return $return;
    }

    /**
     * Calculate the item price, based on any bulk discounts set
     */
    public function onBeforeAdd($item) {
        $object = ($item->StockID) ? CatalogueProduct::get()->filter("StockID", $item->StockID)->first() : null;
        
        if($object) $item->Price = $this->calculate_bulk_price($object, $item);
    }

    /**
     * Calculate the item price, based on any bulk discounts set
     */
    public function onAfterUpdate($item) {
        $object = ($item->StockID) ? CatalogueProduct::get()->filter("StockID", $item->StockID)->first() : null;
        
        if($object) $item->Price = $this->calculate_bulk_price($object, $item);
    }

}

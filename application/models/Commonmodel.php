<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class CommonModel extends CI_Model {



	public function __construct()    {
        parent::__construct();



    }

    /*
     * Invoices list
     */
    //统计发票有效可清算、当前清算、未清算
    private function InvoiceStat( $offers, $invoices, $awards )
    {

        $market['total'] = array(
            'available_amount' => 0,
            'discount_amount' => 0,
            'average_dpe' => 0,
            'average_apr' => 0,
            'vendors' => array()
        );

        $market['nonclearing'] = array(
            'available_amount' => 0,
            'discount_amount' => 0,
            'average_dpe' => 0,
            'average_apr' => 0,
            'list' => array(),
            'vendors' => array()
        );

        $market['clearing'] = array(
            'available_amount' => 0,
            'discount_amount' => 0,
            'average_dpe' => 0,
            'average_apr' => 0,
            'list' => array(),
            'vendors' => array()
        );



    foreach( $offers as $vendor => $offer) {

        foreach ($invoices as $inv) {

            if ($vendor == $inv["Vendorcode"]) {

                $market['total']["average_dpe"] += $inv["Dpe"];

                if ( !in_array($vendor, $market["total"]["vendors"]) )
                {
                    $market["total"]["vendors"][] = $vendor;
                }


                if (isset($awards[$inv["Id"]])) {

                    $market['clearing']["list"][] = array('dpe' => $inv["Dpe"], 'discount' => $awards[$inv["Id"]]["discount"]);
                    $market['clearing']["average_dpe"] += $inv["Dpe"];
                    $market['clearing']["available_amount"] += $inv["InvoiceAmount"];
                    $market['clearing']["discount_amount"] += $awards[$inv["Id"]]["discount"];

                    if ( !in_array($vendor, $market["clearing"]["vendors"]) )
                    {
                        $market["clearing"]["vendors"][] = $vendor;
                    }

                } else {

                    $discount = round($inv["InvoiceAmount"] * $inv["Dpe"] * floatval($offer["offerAPR"]) / 365 / 100, 2);

                    $market['nonclearing']["list"][] = array('dpe' => $inv["Dpe"], 'discount' => $discount);
                    $market['nonclearing']["average_dpe"] += $inv["Dpe"];
                    $market['nonclearing']["available_amount"] += $inv["InvoiceAmount"];
                    $market['nonclearing']["discount_amount"] += $discount;;

                    if ( !in_array($vendor, $market["nonclearing"]["vendors"]) )
                    {
                        $market["nonclearing"]["vendors"][] = $vendor;
                    }
                }
            }
        }

    }
        $market['total']["available_amount"] = $market['clearing']["available_amount"] + $market['nonclearing']["available_amount"];
        $market['total']["discount_amount"] = $market['clearing']["discount_amount"] + $market['nonclearing']["discount_amount"];

        $market['clearing']["average_dpe"] = count($market['clearing']["list"]) > 0 ? round($market['clearing']["average_dpe"] / count($market['clearing']["list"]), 1) : 0;
        $market['nonclearing']["average_dpe"] = count($market['nonclearing']["list"]) > 0 ? round($market['nonclearing']["average_dpe"] / count($market['nonclearing']["list"]), 1) : 0;


        $market['total']["average_dpe"] = count($market['nonclearing']["list"]) > 0 || count($market['clearing']["list"]) > 0 ?
            round($market['total']["average_dpe"] / (count($market['nonclearing']["list"]) + count($market['clearing']["list"])), 1) : 0;

        $avg_apr = 0;

        foreach ($market['clearing']["list"] as $val) {
            $market["clearing"]["average_apr"] += round($val['discount'] / $val['dpe'] * 365 * 100 / $market['clearing']["available_amount"], 2);
            $avg_apr += round($val['discount'] / $val['dpe'] * 365 * 100 / $market['total']["available_amount"], 2);
        }

        foreach ($market['nonclearing']["list"] as $val) {
            $market["nonclearing"]["average_apr"] += round($val['discount'] / $val['dpe'] * 365 * 100 / $market['nonclearing']["available_amount"], 2);
            $avg_apr += round($val['discount'] / $val['dpe'] * 365 * 100 / $market['total']["available_amount"], 2);
        }

        $market['total']["average_apr"] = $avg_apr;


        return $market;

    }


    public  function get_market_list()
    {

    }

}

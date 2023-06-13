<?php
/*
 * ____________________________________________________________
 *
 * Copyright (C) 2016 NICE IT&T
 *
 *
 * This config file may used as it is, there is no warranty.
 *
 * @ description : PHP SSL Client module.
 * @ name        : NicepayLite.php
 * @ author      : NICEPAY I&T (tech@nicepay.co.kr)
 * @ date        :
 * @ modify      : 09.03.2016
 *
 * 09.03.2016 Update Log
 *
 * ____________________________________________________________
 */

namespace libraryCCV2RED;

class Config
{
    const NICEPAY_TIMEOUT_CONNECT = 15;
    const NICEPAY_TIMEOUT_READ = 25;
    const NICEPAY_READ_TIMEOUT_ERR = "10200";

    const NICEPAY_PROGRAM = "NicepayLite";
    const NICEPAY_VERSION = "1.11";
    const NICEPAY_BUILDDATE = "20202606";
    const NICEPAY_REG_URL = "https://www.nicepay.co.id/nicepay/redirect/v2/registration";
    const NICEPAY_ORDER_STATUS_URL = "https://www.nicepay.co.id/nicepay/direct/v2/inquiry";

}
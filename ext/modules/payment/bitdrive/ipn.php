<?php

/*
 * Copyright (c) 2015 IntricateWare Inc.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

chdir('../../../../');
require('includes/application_top.php');

if (!defined('MODULE_PAYMENT_BITDRIVE_STANDARD_STATUS') || (MODULE_PAYMENT_BITDRIVE_STANDARD_STATUS  != 'True')) {
    exit;
}

require('includes/modules/payment/bitdrive_standard.php');

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    exit;
}

// Check for SHA 256 support
if (!in_array('sha256', hash_algos())) {
    exit;
}

$data = file_get_contents('php://input');
$bitdrive_standard = new bitdrive_standard();
$bitdrive_standard->processIpn($data);

tep_session_destroy();

require('includes/application_bottom.php');

?>
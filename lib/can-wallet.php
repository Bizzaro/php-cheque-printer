<?php
require_once dirname(__FILE__) . "/fpdf.php";
require_once dirname(__FILE__) . "/textualnumber.php";

class CheckGenerator
{

    var $checks = array();

    function AddCheck($check)
    {
        $required_fields = array(
            'transit_number',
            'account_number',
            'inst_number',
            'check_number',
            'pay_to',
            'amount',
            'date',
            'from_name',
            'from_address1',
            'from_address2',
            'bank_1',
            'bank_2',
            'bank_3',
            'bank_4',
            'memo'
        );

        $valid = true;

        foreach ($required_fields as $r) {
            if (!array_key_exists($r, $check)) {
                $valid = false;
            }
        }

        if ($valid) {
            $this->checks[] = $check;
            return true;
        } else {
            echo "Missing data for check:<br>";
            print_r(array_diff(array_keys($check), $required_fields));
            print_r(array_diff($required_fields, array_keys($check)));
            return false;
        }
    }


    function PrintChecks()
    {

        ////////////////////////////
        // label-specific variables
        $page_width = 8.5;
        $page_height = 11;

        $top_margin = 0;
        $left_margin = 2.5;

        $columns = 1;
        $gutter = 3 / 16;
        $rows = 3;      // only used for making page breaks, no position calculations

        $label_height = 2.85;
        $label_width  = 6;

        // cell margins
        $cell_left = 0.25;
        $cell_top  = 0.25;
        $cell_bot  = 0.25;

        ////////////////////////////

        $img_ratio = 1.4; // loqisaur
        $img_ratio = .47; // cyan
        $img_ratio = 1.71; // marvelous labs
        $logo_width = 0.66; // loqisaur
        $logo_width = 0.2; // cyan
        $logo_width = 0.5; // marvelous labs

        // Create a PDF with inches as the unit
        $pdf = new FPDF('P', 'in', array($page_width, $page_height));

        $pdf->AddFont('Twcen', '', 'twcen.php');
        $pdf->AddFont('Micr', '', 'micr.php');
        $pdf->AddFont('Courier', '', 'courier.php');

        $pdf->SetMargins($left_margin, $top_margin);
        $pdf->SetDisplayMode("fullpage", "continuous");
        $pdf->AddPage();

        $lpos = 0;
        foreach ($this->checks as $check) {

            $pos = $lpos % ($rows * $columns);

            // calculate coordinates of top-left corner of current cell
            //    margin        cell offset
            $x = $left_margin + (($pos % $columns) * ($label_width + $gutter));
            //    margin        cell offset
            $y = $top_margin  + (floor($pos / $columns) * $label_height);


            /////////////////
            // set up check template

            $pdf->SetFont('Twcen', '', 11);

            // print check number
            $pdf->SetXY($x + 5.25, $y + 0.33);
            $pdf->Cell(1, (11 / 72), $check['check_number'], 0, 'R');

            $logo_offset = 0;  // offset to print name if logo is inserted
            if (array_key_exists('logo', $check) && $check['logo'] != "") {
                // logo should be: 0.71" x 0.29"
                $logo_offset = $logo_width + 0.005;  // width of logo
                $pdf->Image($check['logo'], $x + $cell_left, $y + $cell_top + .12, $logo_width);
            }

            $pdf->SetFont('Twcen', '', 8);

            // name
            $pdf->SetXY($x + $cell_left + $logo_offset, $y + $cell_top + .1);
            $pdf->SetFont('Twcen', '', 10);
            $pdf->Cell(2, (10 / 72), strtoupper($check['from_name']), 0, 2);
            $pdf->SetFont('Twcen', '', 8);
            $pdf->Cell(2, (7 / 72), strtoupper($check['from_address1']), 0, 2);
            $pdf->Cell(2, (7 / 72), strtoupper($check['from_address2']), 0, 2);

            // date
            $pdf->SetFont('Twcen', '', 8);
            $pdf->Line($x + 3.5, $y + .58, $x + 3.5 + 1.3, $y + .58);
            $pdf->SetXY($x + 3.5, $y + .48);
            $date_str = $this->matchcase($check['from_name'], "DATE");
            $pdf->Cell(1, (7 / 72), $date_str);

            // pay to the order of
            $pdf->Line($x + $cell_left, $y + 1.1, $x + $cell_left + 4.1, $y + 1.1);
            $pdf->SetXY($x + $cell_left, $y + .88);
            $pay_str = strtoupper("pay to the order of");
            $pdf->MultiCell(0.7, (7 / 72), $pay_str, 0, 'L');


            // dollar sign
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(4.3);
            $pdf->Cell(-.25, -.25, '$');
            //set font back to twcen
            $pdf->SetFont('Twcen', '', 8);


            // amount box
            $pdf->Rect($x + 4.5, $y + .83, 1.1, .25);

            // dollars
            $pdf->SetFont('Twcen', '', 10);
            $pdf->Line($x + $cell_left, $y + 1.5, $x + $cell_left + 5.37, $y + 1.5);
            $pdf->SetXY($x + $cell_left + 4.37, $y + 1.4);
            $dollar_str = "DOLLARS";
            $pdf->Cell(1, (7 / 72), $dollar_str, '', '', 'R');


            // bank info content
            $pdf->SetFont('Twcen', '', 8);
            $pdf->SetXY($x + $cell_left, $y + 1.6);
            $pdf->Cell(2, (7 / 72), strtoupper($check['bank_1']), 0, 2);
            $pdf->Cell(2, (7 / 72), strtoupper($check['bank_2']), 0, 2);
            $pdf->Cell(2, (7 / 72), strtoupper($check['bank_3']), 0, 2);
            $pdf->Cell(2, (7 / 72), strtoupper($check['bank_4']), 0, 2);


            // memo heading
            $pdf->SetFont('Twcen', '', 8);
            $pdf->Line($x + $cell_left, $y + 2.225, $x + $cell_left + 2.9, $y + 2.225);
            $pdf->SetXY($x + $cell_left, $y + 2.125);
            $memo_str = "MEMO";
            $pdf->Cell(1, (7 / 72), $memo_str);

            // signature line
            $pdf->Line($x + 3.25, $y + 2.225, $x + 3.25 + 2.375, $y + 2.225);

            ///////////////// CONTENT ////////////////
            $pdf->SetFont('Courier', '', 11);

            // date content
            if ($check['date'] != "") {
                $pdf->SetXY($x + 3.5 + .3, $y + .38);
                $pdf->Cell(1, .25, $check['date']);
            }

            // pay to content
            if ($check['pay_to'] != "") {
                $pdf->SetXY($x + $cell_left + 1, $y + .88);
                $pdf->Cell(1, .25, $check['pay_to']);
            }

            // amount content
            if ($check['amount'] > 0) {
                $dollars = intval($check['amount']);
                $cents = round(($check['amount'] - $dollars) * 100);
                //$dollars_str = TextualNumber::GetText($dollars);
                $numtxt = new TextualNumber($dollars);
                $dollars_str = $numtxt->numToWords($dollars);

                $amt_string = ucfirst(strtoupper($dollars_str)) . " DOLLARS";
                if ($cents > 0) {
                    $amt_string .= " AND " . $cents . "/100";
                } else {
                    $amt_string .= " AND 00/100";
                }
                // $amt_string .= "***";

                $pdf->SetFont('Courier', '', 9);
                $pdf->SetXY($x + $cell_left, $y + 1.28);
                $pdf->Cell(1, .25, $amt_string);

                #$amt = '$'.sprintf("%01.2f",$check['amount']);
                $amt = number_format($check['amount'], 2);

                $pdf->SetXY($x + 4.5 + .06, $y + .83);
                $pdf->Cell(1, .25, $amt);
            }

            // memo content
            $pdf->SetFont('Courier', '', 8);
            $pdf->SetXY($x + $cell_left + 0.5, $y + 2.04);
            $pdf->Cell(1, .25, $check['memo']);
            $pdf->SetFont('Courier', '', 11);

            // routing and account number
            $pdf->SetFont('Micr', '', 10);
            // t = transit number symbol
            // o = on-us symbol
            // d = dash
            $routingstring = "o" . $check['check_number'] . "o   t" . $check['transit_number'] . "d" . $check['inst_number'] . "t" . $this->getSpacesByInstitution($check['inst_number']) . $this->replaceDashesWithD($check['account_number']) . "o";
            if (array_key_exists('codeline', $check))
                $routingstring = $check['codeline'];
            $pdf->SetXY($x + $cell_left, $y + 2.47);
            $pdf->Cell(5, (10 / 72), $routingstring);


            // signature
            if (substr($check['signature'], -3) == 'png') {
                $sig_offset = 1.75;  // width of signature
                $pdf->Image($check['signature'], $x + $cell_left + 3.4, $y + 1.88, $sig_offset);
            } else {
                $pdf->SetFont('Arial', 'i', 10);
                if ($check['signature'] != "") {
                    $pdf->SetXY($x + $cell_left + 3.4, $y + 2.01);
                    $pdf->Cell(1, .25, $check['signature']);
                }
            }

            if ($pos == (($rows * $columns) - 1) && !($lpos == count($this->checks) - 1)) {
                $pdf->AddPage();
            }

            $lpos++;
        }

        $pdf->Output();
    }


    // private, returns $str capitalized to match with $name
    function matchcase($name, $str)
    {
        // check if first letter is uppercase
        if (strtoupper(substr($name, 0, 1)) == substr($name, 0, 1)) {
            return ucfirst($str);
        } else {
            return strtolower($str);
        }
    }

    function replaceDashesWithD($accountNumber)
    {
        return str_replace('-', 'd', $accountNumber);
    }

    // defines separation between inst number and account number
    function getSpacesByInstitution($institutionNumber)
    {
        $spacesMap = [
            '001' => 2, // BMO
            '002' => 1, // BNS
            '003' => 3, // RBC
            '004' => 1, // TD
            '006' => 3, // NBC
            '010' => 1, // CIBC
            '815' => 3, // DESJ
            '828' => 0, // CU
        ];

        // Default spaces if the institution number is not found
        $defaultSpaces = 3;

        // Get the number of spaces for the given institution number or fallback to default
        $numSpaces = $spacesMap[$institutionNumber] ?? $defaultSpaces;

        // Return the spaces as a string
        return str_repeat(' ', $numSpaces);
    }
}

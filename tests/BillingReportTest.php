<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../helpers/billing-calc.php';

class BillingReportTest extends TestCase
{
    // -----------------------------------------------------------------------
    // calcGliderCharge tests
    // -----------------------------------------------------------------------

    /** @dataProvider gliderChargeProvider */
    public function testCalcGliderCharge(
        $clubGlider, $regoShort, $totMins, $memberClass, $cpm, $maxCap, $expected)
    {
        $result = calcGliderCharge($clubGlider, $regoShort, $totMins, $memberClass, $cpm, $maxCap);
        $this->assertEquals($expected, $result, "Glider charge mismatch", 0.005);
    }

    public function gliderChargeProvider(): array
    {
        return [
            'Full flying GGR 10min $2.25' => [1, 'GGR', 10, 'Flying', 2.25, 180.00, 22.50],
            'Full flying GPJ 30min $2.25' => [1, 'GPJ', 30, 'Flying', 2.25, 180.00, 67.50],
            'Full flying GNB 10min $2.25' => [1, 'GNB', 10, 'Flying', 2.25, 180.00, 22.50],

            'Youth on GGR 10min $1.50' => [1, 'GGR', 10, 'Youth', 2.25, 180.00, 15.00],
            'Youth on GPJ 10min $1.50' => [1, 'GPJ', 10, 'Youth', 2.25, 180.00, 15.00],
            'Youth on GMB 10min $1.50' => [1, 'GMB', 10, 'Youth', 2.25, 180.00, 15.00],

            'Youth on GNB 10min $2.25 (no discount)' => [1, 'GNB', 10, 'Youth', 2.25, 180.00, 22.50],
            'Youth on unknown glider 10min $2.25' => [1, 'XYZ', 10, 'Youth', 2.25, 180.00, 22.50],

            'Non-club glider = $0' => [0, 'GGR', 10, 'Flying', 2.25, 180.00, 0.00],
            'Zero rate glider = $0' => [1, 'GNP', 10, 'Flying', 0.00, 0.00, 0.00],

            'Cap at 180 on GGR 100min $2.25' => [1, 'GGR', 100, 'Flying', 2.25, 180.00, 180.00],
            'Just under cap on GGR 79min $2.25' => [1, 'GGR', 79, 'Flying', 2.25, 180.00, 177.75],
            'Youth cap at 180 on GGR 180min $1.50' => [1, 'GGR', 180, 'Youth', 2.25, 180.00, 180.00],

            'No cap but long flight' => [1, 'GGR', 200, 'Flying', 2.25, 0.00, 450.00],

            'Case insensitive Youth' => [1, 'GGR', 10, 'youth', 2.25, 180.00, 15.00],
            'Short Term no discount' => [1, 'GGR', 10, 'Short Term', 2.25, 180.00, 22.50],
        ];
    }

    // -----------------------------------------------------------------------
    // calcLaunchCharge tests
    // -----------------------------------------------------------------------

    /** @dataProvider launchChargeProvider */
    public function testCalcLaunchCharge($launchType, $isFirstWinch, $expected)
    {
        $TOW = 1; $WINCH = 2; $SELF = 3;
        $result = calcLaunchCharge($launchType, $isFirstWinch, $TOW, $WINCH, $SELF);
        $this->assertEquals($expected, $result, "Launch charge mismatch", 0.005);
    }

    public function launchChargeProvider(): array
    {
        $TOW = 1; $WINCH = 2; $SELF = 3;
        return [
            'First winch launch = $39'  => [$WINCH, true, 39.00],
            'Relaunch winch = $25'      => [$WINCH, false, 25.00],
            'Self-launch = $25'         => [$SELF, false, 25.00],
            'Aerotow (competition) = $0' => [$TOW, false, 0.00],
            'Unknown launch = $0'       => [99, false, 0.00],
        ];
    }

    // -----------------------------------------------------------------------
    // isCompetitionFlight tests
    // -----------------------------------------------------------------------

    /** @dataProvider compFlightProvider */
    public function testIsCompetitionFlight($launchType, $expected)
    {
        $TOW = 1;
        $result = isCompetitionFlight($launchType, $TOW);
        $this->assertSame($expected, $result);
    }

    public function compFlightProvider(): array
    {
        return [
            'Aerotow is competition'    => [1, true],
            'Winch is not competition'  => [2, false],
            'Self-launch is not comp'   => [3, false],
        ];
    }

    // -----------------------------------------------------------------------
    // isFiftyFifty tests
    // -----------------------------------------------------------------------

    /** @dataProvider fiftyFiftyProvider */
    public function testIsFiftyFifty($billPic, $billP2, $expected)
    {
        $result = isFiftyFifty($billPic, $billP2);
        $this->assertSame($expected, $result);
    }

    public function fiftyFiftyProvider(): array
    {
        return [
            'Both set = 50/50'          => [1, 1, true],
            'Only PIC billed'           => [1, 0, false],
            'Only P2 billed'            => [0, 1, false],
            'Neither billed'            => [0, 0, false],
        ];
    }

    // -----------------------------------------------------------------------
    // getLaunchLabel tests
    // -----------------------------------------------------------------------

    /** @dataProvider launchLabelProvider */
    public function testGetLaunchLabel($launchType, $expected)
    {
        $TOW = 1; $WINCH = 2; $SELF = 3;
        $result = getLaunchLabel($launchType, $TOW, $WINCH, $SELF);
        $this->assertSame($expected, $result);
    }

    public function launchLabelProvider(): array
    {
        return [
            'Aerotow label'  => [1, 'AEROTOW'],
            'Winch label'    => [2, 'WINCH'],
            'Self label'     => [3, 'SELF'],
            'Other label'    => [99, 'OTHER'],
        ];
    }

    // -----------------------------------------------------------------------
    // End-to-end test: page loads and contains data
    // -----------------------------------------------------------------------

    /** @group e2e */
    public function testPageLoadsAndShowsFlights(): void
    {
        $client = loginClient();

        $resp = $client->post('/BillingReport', [
            'form_params' => [
                'month' => date('m'),
                'year'  => date('Y'),
                'view'  => 'View Report',
            ],
        ]);

        $this->assertEquals(200, $resp->getStatusCode());
        $html = (string)$resp->getBody();

        $this->assertStringContainsString('Treasurer Monthly Report', $html);
        $this->assertStringContainsString('Flight Charges', $html);
    }

    /** @group e2e */
    public function testCsvExportDownloads(): void
    {
        $client = loginClient();

        $resp = $client->post('/BillingReport.csv', [
            'form_params' => [
                'month'  => date('m'),
                'year'   => date('Y'),
                'export' => 'Export CSV',
            ],
        ]);

        $this->assertEquals(200, $resp->getStatusCode());
        $contentType = $resp->getHeaderLine('Content-Type');
        $this->assertStringContainsString('text/csv', $contentType);

        $body = (string)$resp->getBody();
        $this->assertStringContainsString('SURNAME', $body);
    }

    // -----------------------------------------------------------------------
    // Verify the calculation results are correct by comparing known values
    // -----------------------------------------------------------------------

    /**
     * @group e2e
     * @dataProvider knownFlightProvider
     */
    public function testKnownFlightCalculationProducesCorrectCharges(
        $clubGlider, $rego, $minutes, $memberClass, $cpm, $cap,
        $launchType, $isFirstWinch,
        $expectedGlider, $expectedLaunch, $expectedTotal)
    {
        $TOW = 1; $WINCH = 2; $SELF = 3;

        $glider = calcGliderCharge($clubGlider, $rego, $minutes, $memberClass, $cpm, $cap);
        $launch = calcLaunchCharge($launchType, $isFirstWinch, $TOW, $WINCH, $SELF);
        $total = $glider + $launch;

        $this->assertEquals($expectedGlider, $glider, 'Glider charge', 0.005);
        $this->assertEquals($expectedLaunch, $launch, 'Launch charge', 0.005);
        $this->assertEquals($expectedTotal, $total, 'Total charge', 0.005);
    }

    public function knownFlightProvider(): array
    {
        // Scenario: Flying member, first winch, 15min flight in GGR
        // Glider: 15 * 2.25 = 33.75, Launch: 39.00, Total: 72.75
        return [
            'Flying GGR 15min first winch' => [1, 'GGR', 15, 'Flying', 2.25, 180, 2, true, 33.75, 39.00, 72.75],
            'Flying GGR 15min relaunch' => [1, 'GGR', 15, 'Flying', 2.25, 180, 2, false, 33.75, 25.00, 58.75],
            'Youth GGR 10min first winch' => [1, 'GGR', 10, 'Youth', 2.25, 180, 2, true, 15.00, 39.00, 54.00],
            'Youth GNB 10min first winch' => [1, 'GNB', 10, 'Youth', 2.25, 180, 2, true, 22.50, 39.00, 61.50],
            'Flying GGR 80min cap winch' => [1, 'GGR', 80, 'Flying', 2.25, 180, 2, true, 180.00, 39.00, 219.00],
            'Flying self-launch landing fee' => [1, 'GGR', 10, 'Flying', 2.25, 180, 3, false, 22.50, 25.00, 47.50],
            'Private glider no charge' => [0, 'ABC', 10, 'Flying', 2.25, 180, 2, true, 0.00, 39.00, 39.00],
        ];
    }
}

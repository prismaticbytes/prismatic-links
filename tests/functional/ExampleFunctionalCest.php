<?php

namespace prismaticbytes\prismaticlinkstests\acceptance;

use Craft;
use FunctionalTester;

class ExampleFunctionalCest
{
    // Public methods
    // =========================================================================

    // Tests
    // =========================================================================

    /**
     *
     */
    public function testController(FunctionalTester $I)
    {
        $I->amLoggedInAs(1);

        $I->amOnPage('/actions/prismatic-links/default/parse/?url=https://en.wikipedia.org/wiki/South_Africa');

        $I->seeResponseCodeIs(200);

        // $src = $I->grabPageSource();
        // $data = json_decode($src);

        $I->see('"url":"https://en.wikipedia.org/wiki/South_Africa"');
        $I->see('"image":"https://upload.wikimedia.org/wikipedia/commons/thumb/a/af/Flag_of_South_Africa.svg/1200px-Flag_of_South_Africa.svg.png"');
        $I->see('"title":"South Africa - Wikipedia"');
        $I->see('"valid":true');

    }
}

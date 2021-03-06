<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('perform actions login');
$I->amOnPage('/');
$I->see('Antarctic Auction');
//test for main menu link list
$I->seeLink('Home');
$I->click('Home');
$I->seeInCurrentUrl('/');
$I->seeLink('Current Inventory');
$I->click('Current Inventory');
$I->see('All Posted Items');
$I->seeInCurrentUrl('/auction-list');
$I->seeLink('How it Works');
$I->click('How it Works');
$I->seeInCurrentUrl('/how-it-works');
$I->seeLink('About');
$I->click('About');
$I->see('About the US Antarctic Auction');
$I->seeInCurrentUrl('/about');
$I->seeLink('Contact');
$I->click('Contact');
$I->seeInCurrentUrl('/contact');
$I->seeLink('Watch List');
$I->click('Watch List');
$I->seeInCurrentUrl('/watch-list');
$I->see('There are no auctions in your watch list.');
//test for footer menu list
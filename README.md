AzineMailgunWebhooksBundle
==========================

Symfony2 Bundle to capture event data from the Mailgun.com transactional mail service.

If you are using a free mailgun.com account (less than 10'000 email per month), then
mailgun.com deletes log-entries about events after about 48 hours. 

So if you want to check who recieved the newsletter you sent last week, your busted. :-(

Mailgun.com offeres the cool feature to post the event data to an URL of your choice.
=> see http://documentation.mailgun.com/user_manual.html#webhooks for more details.

This bundle captures this data. You can search for, filter and display log-entries and
delete them when you don't need them anymore (or when you need to save some disk-space).  


## Features
- capture all data that mailgun.com can post via the "webhooks" provided by mailgun.com => http://documentation.mailgun.com/user_manual.html#webhooks
- display lists of event entries with search and filter functionality
- show all details of a singel event
- delete events 

## Installation
To install AzineMailgunWebhooksBundle with Composer just add the following to your `composer.json` file:

```
// composer.json
{
    // ...
    require: {
        // ...
        "azine/mailgunwebhooks-bundle": "dev-master",
    }
}
```
Then, you can install the new dependencies by running Composer’s update command from 
the directory where your `composer.json` file is located:

```
php composer.phar update
```
Now, Composer will automatically download all required files, and install them for you. 
All that is left to do is to update your AppKernel.php file, and register the new bundle:

```
<?php

// in AppKernel::registerBundles()
$bundles = array(
    // ...
    new Azine\MailgunWebhooksBundle\AzineMailgunWebhooksBundle(),
    // ...
);
```

Register the routes of the AzineMailgunWebhooksBundle:

```
// in app/config/routing.yml

# Route for mailgun.com to post the information that we want to store in the database
azine_mailgun_webhooks_bundle_webhook:
    resource: "@AzineMailgunWebhooksBundle/Resources/config/routing/mailgunevent_webhook.yml"
    prefix:   /

# Routes for administration of the posted data
azine_mailgun_webhooks_bundle_admin:
    resource: "@AzineMailgunWebhooksBundle/Resources/config/routing/mailgunevent_admin.yml"
    prefix:   /admin/
    
```

## Configuration options
This is the complete list of configuration options with their defaults.
```
// app/config/config.yml
# Default configuration for "AzineMailgunWebhooksBundle"
azine_mailgun_webhooks:

    # Your api-key for mailgun => see https://mailgun.com/cp
    api_key:              ~ # Required

    # Your public-api-key for mailgun => see https://mailgun.com/cp
    public_api_key:       ''
```

# Sidenote on "monolog" emails and web scanners
You can configure monolog to send emails whenever an error occurs.
=> http://symfony.com/doc/current/cookbook/logging/monolog_email.html 

It is likely that many 404-errors occur on you site because web-scanners 
try to see if you are hosting vulnerable scripts on your server. If 
these errors are mailed via mailgun.com as well, you might send a lot more 
mails than you want to (and exceed the limit of 10k free emails) and it  
will clutter you database with more or less useless information.

Since Symfony 2.4, to avoid these emails being sent, you can configure 
monolog to ignore certain 404 errors.
=> http://symfony.com/doc/current/cookbook/logging/monolog_regex_based_excludes.html  

```
// app/config/config.yml
monolog:
    handlers:
        main:
            type:         fingers_crossed
            action_level: warning
            handler:      yourNextHandler
            excluded_404s:
                - ".*/cgi-bin/php.*"
                - ".*MyAdmin/scripts/setup.php.*"
                - ".*autoconfig/mail/config-v1.1.xml.*"
                - ".*vtigercrm/graph.php.*"
                - ".*/HNAP1/.*"
                - ".*calendar/install/index.php.*"
                - ".*admin/config.php.*"
```

# Webhooks configuration of mailgun.com
To tell mailgun.com to post the data to your database via the webhooks, just
get the full url of the "mailgunevent_webhook"-route

```
# on a bash console execute this to get the absolute webhook path
php bin/console debug:router -e prod | grep mailgunevent_webhook 
// note for Symfony 2.x it is 'php app/console debug:router -e prod | grep mailgunevent_webhook'

```

and copy it to all the input fields for the webhooks on:

- https://mailgun.com/cp/log#drop-callback
- https://mailgun.com/cp/stats#open-webhook-url
- https://mailgun.com/cp/bounces#bounce-callback
- https://mailgun.com/cp/unsubscribes#unsubscribe-callback
- https://mailgun.com/cp/spamreports#spam-callback
- https://mailgun.com/cp/routes

Then test if everything is setup ok by clicking the "Test" or "Send" button and check
you database or the event-list.

```
# on a bash console execute this to get the absolute overview-page path
php bin/console router:debug -e prod | grep mailgun_overview
// note for Symfony 2.x it is 'php app/console debug:router -e prod | grep mailgun_overview'
```

## Events
Whenever mailgun posts an event via the webhook, an MailgunWebhookEvent containing the 
new MailgunEvent is dispatched.

You can implement your own means of notification for failures or if you configured your
application to use the swiftmailer, you can use the SwiftMailerMailgunWebhookEventListener,
to send emails to an address you specified.

# ToDos / Contribute
Anyone is welcome to contribute.

Here's a list of open TODOs
- write more unit-tests
- add commands to "cleanup" the database periodically
- add SwiftMailerMailgunWebhookEventListener to notify admins when certain events occur => email upon "dropped" event
- write some CSS, style pages 




## Build-Status ec.

[![Build Status](https://travis-ci.org/azine/AzineMailgunWebhooksBundle.png)](https://travis-ci.org/azine/AzineMailgunWebhooksBundle)
[![Total Downloads](https://poser.pugx.org/azine/mailgunwebhooks-bundle/downloads.png)](https://packagist.org/packages/azine/mailgunwebhooks-bundle)
[![Latest Stable Version](https://poser.pugx.org/azine/mailgunwebhooks-bundle/v/stable.png)](https://packagist.org/packages/azine/mailgunwebhooks-bundle)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/azine/AzineMailgunWebhooksBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/azine/AzineMailgunWebhooksBundle/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/azine/AzineMailgunWebhooksBundle/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/azine/AzineMailgunWebhooksBundle/?branch=master)
[![Dependency Status](https://www.versioneye.com/user/projects/567eaea7eb4f47003c000015/badge.svg?style=flat)](https://www.versioneye.com/user/projects/567eaea7eb4f47003c000015)

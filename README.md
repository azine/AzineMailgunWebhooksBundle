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

# ToDos
- create index-page showing all the options of this bundle
- write unit-tests
- add filter-options to only keep "interesting" events => delete "warnings" that were resolved later
- add commands to "cleanup" the database periodically
- add extension-hooks/throw events to notify admins when certain events occur => email upon email-failure
- write ajax action to delete single entries from list without reload



[![Build Status](https://travis-ci.org/azine/AzineMailgunWebhooksBundle.png)](https://travis-ci.org/azine/AzineMailgunWebhooksBundle)
[![Total Downloads](https://poser.pugx.org/azine/mailgunwebhooks-bundle/downloads.png)](https://packagist.org/packages/azine/mailgunwebhooks-bundle)
[![Latest Stable Version](https://poser.pugx.org/azine/mailgunwebhooks-bundle/v/stable.png)](https://packagist.org/packages/azine/mailgunwebhooks-bundle)
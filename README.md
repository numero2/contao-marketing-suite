Contao Marketing Suite
=======================

[![](https://img.shields.io/packagist/v/numero2/contao-marketing-suite.svg?style=flat-square)](https://packagist.org/packages/numero2/contao-marketing-suite) ![](https://img.shields.io/badge/license-commercial-blue.svg?style=flat-square)

About
--

The package adds marketing functionalities to Contao. The Contao Marketing Suite enables dynamic playout of content to provide visitors with relevant information. Furthermore there is A/B testing, SEO support, text creation tools, custom tracking for links and forms. In addition, a button generator, a configurable cookie bar (already compliant with EU privacy) and many other marketing functions for professional marketing with Contao.

Insert Tags
--

| Tag    | Description                                                                          |
| ------ | ------------------------------------------------------------------------------------ |
| `{{cms_optinlink}}` | Creates a link that redisplays the cookie consent dialog so that the user can agree to the use of the necessary group. After consent, the browser window will automatically scroll to the original element. |
| `{{ifoptin::*}}`    | This tag is completely removed if the corresponding element was not approved. The parameter here is the ID of the tag (e.g. Google Analytics). This way, content in templates can be played depending on whether the user has agreed to the use of a certain tag. `{{ifoptin::1}}<h1>Has agreed</h1>{{ifoptin}}` |
| `{{ifnoptin::*}}`   | This tag will be removed completely if the corresponding element has been approved. The parameter here is the ID of the tag (e.g. Google Analytics). This way, content in templates can be played depending on whether the user has not agreed to the use of a particular tag. `{{ifnoptin::1}}<h1>Did not agree</h1>{{ifnoptin}}` |

For Developers
--

### Helper Functions for Cookie Consent

For Developers the Marketing Suite provides some helper functions in order to integrate the cookie consent handling into your own extensions.

These functions will check if the tag, given by its id, has been accepted by the visitor. The function also take care if the tag itself is actually set active or not.

For example in a `html5` template you can use it like this:
```php
<?php if( \numero2\MarketingSuite\Helper\Tag::isAccepted(6) ): ?>
    <!-- YOUR CONTENT IF ACCEPTED -->
<?php endif; ?>
<?php if( \numero2\MarketingSuite\Helper\Tag::isNotAccepted(6) ): ?>
    <!-- YOUR CONTENT IF NOT ACCEPTED -->
<?php endif; ?>
```

We also provide a twig function to be used inside `twig` templates like this:

```twig
{% if cms_tag_accepted(6) %}
    <!-- YOUR CONTENT IF ACCEPTED -->
{% endif %}
{% if cms_tag_not_accepted(6) %}
    <!-- YOUR CONTENT IF NOT ACCEPTED -->
{% endif %}
```

### Header to disable tracking

Certain elements in the Marketing Suite can be tracked (like a click on a CTA or the view of an element). In case you want to prevent certain requests from actually tracking something the Suite provides a special HTTP header called `X-CMS-DNT`. If this header is present the tracking will be disabled for this request.

System requirements
--

* [Contao 5.3](https://github.com/contao/contao) (or newer)
* [Contao 4.x](https://github.com/contao/contao) is only supported up to Marketing Suite version [2.1.5](https://github.com/numero2/contao-marketing-suite/releases/tag/2.1.5)

Installation
--

* See [contao-marketingsuite.com](https://contao-marketingsuite.com) for details.
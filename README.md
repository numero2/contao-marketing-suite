Contao Marketing Suite
=======================

[![](https://img.shields.io/packagist/v/numero2/contao-marketing-suite.svg?style=flat-square)](https://packagist.org/packages/numero2/contao-marketing-suite) ![](https://img.shields.io/badge/license-commercial-blue.svg?style=flat-square)

About
--
The package adds marketing functionalities to Contao. The Contao Marketing Suite enables dynamic playout of content to provide visitors with relevant information. Furthermore there is A/B testing, SEO support, text creation tools, custom tracking for links and forms. In addition, a button generator, a configurable cookie bar (already compliant with EU privacy) and many other marketing functions for professional marketing with Contao.

Insert Tags
--

| Tag                 | Description                                                  |
| ------------------- | ------------------------------------------------------------ |
| `{{cms_optinlink}}` | Creates a link that redisplays the cookie consent dialog so that the user can agree to the use of the necessary group. After consent, the browser window will automatically scroll to the original element. |
| `{{ifoptin::*}}` | This tag is completely removed if the corresponding element was not approved. The parameter here is the ID of the tag (e.g. Google Analytics). This way, content in templates can be played depending on whether the user has agreed to the use of a certain tag. `{{ifoptin::1}}<h1>Has agreed</h1>{{ifoptin}}` |
| `{{ifnoptin::*}}` | This tag will be removed completely if the corresponding element has been approved. The parameter here is the ID of the tag (e.g. Google Analytics). This way, content in templates can be played depending on whether the user has not agreed to the use of a particular tag. `{{ifnoptin::1}}<h1>Did not agree</h1>{{ifnoptin}}` |

For Developers
--
For Developers the Marketing Suite provides some helper functions in order to integrate the cookie consent handling into your own extensions.

These functions will check if the tag, given by its id, has been accepted by the visitor. The function also take care if the tag itself is actually set active or not.

```php
<?php if( \numero2\MarketingSuite\Helper\Tag::isAccepted(6) ): ?>
	<!-- YOUR CONTENT IF ACCEPTED -->
<?php endif; ?>
<?php if( \numero2\MarketingSuite\Helper\Tag::isNotAccepted(6) ): ?>
   	<!-- YOUR CONTENT IF NOT ACCEPTED -->
<?php endif; ?>
```

System requirements
--

* [Contao 4.9](https://github.com/contao/contao)


Installation
--

* See [contao-marketingsuite.com](https://contao-marketingsuite.com) for details.
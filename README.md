🖒 (module for Omeka S)
======================

> __New versions of this module and support for Omeka S version 3.0 and above
> are available on [GitLab], which seems to respect users and privacy better
> than the previous repository.__

[🖒] is a module for [Omeka S] that allows visitors to like or dislike resources
via a simple click on a button. The module supports five icon shape combinations:
heart (❤️/💔), thumb (👍/👎), reversed thumb (🖒/🖓), and mixed combinations (👍/🖓
or 🖒/👎). Heart is the default icon shape.


**About the name of the module**

The module is named "🖒" (Unicode character U+1F592, Reversed thumbs up sign).
The main purpose of this choice is to check current php version, web services,
browsers, graphical interfaces and operating systems with a real life feature.
In particular, most browsers bypass the right font character with specific icon.
And historically, the implementation of the unicode in php was complex and
caused the [fall of php 6].

Workarounds are included in the modules to make it working, except in some
non-friendly and anti-privacy systems. The aim is to remove them. Furthermore,
this module adds a useless feature of the web, so if it cannot be installed, it
can be skipped in most of the cases.

Note: The character 🖒 (U+1F592) differs from the more common 👍 (U+1F44D) in
order to force the orientation of the icon to be like common representation
(wrist on left) and integrate smoothly in the code (from left to right).
In fact, the choice of the right or left hand is not explicitely defined for
this icon, but the general recommandations is to be standard, so 90% of people
use right hand, so the right representation of U+1F44D should be right hand. And
this is the choice of most fonts and font-designers. Nevertheless, some dominant
companies that don't care about people and standards that are not theirs, chose
to use left hand, so it is the most common mental representation of users now.

Anyway, an option allows to specify any of the unicode symbol in the module.
Default is ❤.


Installation
------------

See general end user documentation for [installing a module].

The module requires the modules [Common], version 3.4.74 or later, and [Guest],
version 3.4.11 or later.

* From the zip

Download the last release [🖒.zip] from the list of releases, and
uncompress it in the `modules` directory.

* From the source and for development

If the module was installed from the source, rename the name of the folder of
the module to `🖒`, go to the root of the module, and run:

```sh
composer install --no-dev
```

* For test

The module includes a test suite with unit and functional tests.
Run them from the root of Omeka:

```sh
vendor/bin/phpunit -c modules/🖒/phpunit.xml --testdox
```


Quick Start
-----------

After installation:

1. Configure the module in the admin settings to enable likes on desired
   resource types (items, item sets, media).
2. Configure the display options:
   - Icon shape: heart (default), thumb (👍/👎), reversed thumb (🖒/🖓),
     or mixed combinations (👍/🖓, 🖒/👎)
   - Icon type: Unicode (emoji) or Font Awesome
   - Allow dislike: disabled by default
   - Allow users to change their vote: enabled by default
   - Allow anonymous visitors to vote: disabled by default
   - Show/hide like and dislike counts
3. Add the "🖒: Button" resource page block to your site resource page templates.
4. Authenticated users can now like (and optionally dislike) resources.

Anonymous voting can be enabled globally or per site. To avoid double votes, a
long-lived cookie storing a random token identifies the anonymous visitor and a
unique constraint prevents a second vote on the same resource. This is a
best-effort deduplication: it can be bypassed by clearing cookies or switching
browser.

When an anonymous visitor later authenticates, the votes cast with that cookie
are claimed: each one is transferred to the account, or dropped if the user
already voted on the resource. So votes follow the user at login and are not
counted twice across the anonymous/authenticated transition.


Development / themes
--------------------

### View Helper

The `🖒()` view helper can be used in themes:

```php
// Basic usage - uses current resource and user from view
echo $this->🖒();

// With specific resource
echo $this->🖒($resource);

// With options
echo $this->🖒($resource, null, [
    'showCount🖒' => true,
    'showCount🖓' => false,
    // 'heart', 'thumb', 'reverse', 'thumb-reverse', or 'reverse-thumb'
    'iconShape' => 'heart',
    // 'unicode' or 'fa'
    'iconType' => 'unicode',
    'allow🖓' => false,
    // Allow users to change/remove their vote
    'allowChangeVote' => true,
    // Custom template
    'template' => 'common/🖒',
]);
```

### Sort

Resources can be sorted by like counts using API parameters:

- `sort_by=like_count` - Sort by number of likes
- `sort_by=dislike_count` - Sort by number of dislikes
- `sort_by=vote_count` - Sort by total votes


Guest Integration
-----------------

When the [Guest] module is installed, users can access their liked resources at
`/s/my-site/guest/like`. A widget is automatically added to the guest dashboard
showing the user's likes count.

You can also add a "My Likes" link to your site navigation using the navigation
link type "My Likes".

### Site Settings for Guest

The following site settings are available to customize the guest experience:

- Guest widget label: Label shown in the guest dashboard widget (default: "Likes")
- Guest link label: Label for the link in the widget, use %d for count (default:
  "My likes (%d)")
- Guest page title: Title of the guest likes page (default: "My Likes")


TODO
----

- [ ] Add like notifications
- [ ] Add like reports/statistics
- [x] Add a way to allow anonymous like (one time only)
- [ ] Extend likes to entities with a separate id space (Selection, etc.):
      the `like` table indexes `resource_id` without an entity-type column and
      references `resource(id)`, so a `Selection #45` and an `Item #45` would
      collide on the same counter. Add an `entity_name` discriminator column
      (composite index/unique on `entity_name` + `resource_id`) and drop the
      strict foreign key to `resource` before enabling non-resource likes.


Warning
-------

Use it at your own risk.

It’s always recommended to backup your files and your databases and to check
your archives regularly so you can roll back if needed.

```sh
# database dump example
mariadb-dump -u omeka -p omeka | gzip > "omeka.$(date +%Y%m%d_%H%M%S).sql.gz"
```


Troubleshooting
---------------

See online issues on the [module issues] page on GitLab.


License
-------

This module is published under the [CeCILL v2.1] license, compatible with
[GNU/GPL] and approved by [FSF] and [OSI].

This software is governed by the CeCILL license under French law and abiding by
the rules of distribution of free software. You can use, modify and/ or
redistribute the software under the terms of the CeCILL license as circulated by
CEA, CNRS and INRIA at the following URL "http://www.cecill.info".

As a counterpart to the access to the source code and rights to copy, modify and
redistribute granted by the license, users are provided only with a limited
warranty and the software’s author, the holder of the economic rights, and the
successive licensors have only limited liability.

In this respect, the user’s attention is drawn to the risks associated with
loading, using, modifying and/or developing or reproducing the software by the
user in light of its specific status of free software, that may mean that it is
complicated to manipulate, and that also therefore means that it is reserved for
developers and experienced professionals having in-depth computer knowledge.
Users are therefore encouraged to load and test the software’s suitability as
regards their requirements in conditions enabling the security of their systems
and/or data to be ensured and, more generally, to use and operate it in the same
conditions as regards security.

The fact that you are presently reading this means that you have had knowledge
of the CeCILL license and that you accept its terms.

Copyright
---------

- Copyright Daniel Berthereau, 2025 (see [Daniel-KM] on GitLab)

This module was designed for [Musée de Bretagne].


[🖒]: https://gitlab.com/Daniel-KM/Omeka-S-module-Like
[Omeka S]: https://omeka.org/s
[fall of php 6]: https://en.wikipedia.org/wiki/PHP#PHP_6_and_Unicode
[installing a module]: https://omeka.org/s/docs/user-manual/modules/#installing-modules
[Common]: https://gitlab.com/Daniel-KM/Omeka-S-module-Common
[Guest]: https://gitlab.com/Daniel-KM/Omeka-S-module-Guest
[GitLab]: https://gitlab.com/Daniel-KM/Omeka-S-module-Like
[🖒.zip]: https://gitlab.com/Daniel-KM/Omeka-S-module-Like/-/releases
[module issues]: https://gitlab.com/Daniel-KM/Omeka-S-module-Like/-/issues
[CeCILL v2.1]: https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: http://opensource.org
[Musée de Bretagne]: https://collections.musee-bretagne.fr
[GitLab]: https://gitlab.com/Daniel-KM
[Daniel-KM]: https://gitlab.com/Daniel-KM "Daniel Berthereau"

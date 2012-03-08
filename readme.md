# Reflective Journal plugin for Moodle 2

A [reflective journal](http://www.open.ac.uk/skillsforstudy/keeping-a-reflective-learning-journal.php) activity for Moodle 2, based on the 3rd party Journal plugin.

## Purpose

We used the Journal module previously, but came across two limitations:

*   **You can only make one entry.** A limitation of the current Journal module is that each user can only make one entry for each journal (which would exacerbate the next issue).

*   **You can only store 64k (65,535 characters) of text.** This isn't a major concern most of the time, but is nevertheless a hard limit and it can be quickly used up. When writing reflective journals with the Journal plugin, our students would hit this limit and there's nothing they can do.

> **Note:** There was also the issue of any more than 64k of data would be silently truncated, meaning that users would lose data but _not know that they had lost it_.

We wanted a module which worked in broadly the same way as the existing Journal module but with our own customisations and without the issues listed above.

## Features

*   The _Reflective Journal_ acts somewhat like a blog, allowing the user to make as many entries as they want/need, whenever they wish.

*   It is a private place to write, visible only to the user and the course teachers.

*   Each entry can be individually marked by a teacher, using marking criteria set when the journal was created. This creates an overall mark for the journal.

*   Technically, we can store much more text then previously able (Up to 16Mb, or 16.7 million characters), but the use of multiple entries instead of just one negates the need for this.

### Based on the Journal module

This plugin is based on the [Journal module](http://moodle.org/plugins/view.php?plugin=mod_journal) ([Moodle Tracker](http://tracker.moodle.org/browse/CONTRIB/component/10880), [GitHub](https://github.com/dmonllao/moodle-mod_journal)) which is currently maintained by [David Monlla&oacute;](http://moodle.org/user/profile.php?id=122326).

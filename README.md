# TYPO3 Extension `uppload`

> This TYPO3 extension brings uppload.js.org to TYPO3 to do an extended file upload in the frontend or the backend of TYPO3.
> At the moment it brings the functinality as a file upload field for Powermail.

## 1 Features

* Adds new upload field to use with Powermail
* Adds possibility to configure it over extension settings

## 2 Usage

### 2.1 Installation

#### Installation using Composer

The recommended way to install the extension is using [Composer][1].

Run the following command within your Composer based TYPO3 project:

```
composer require ehaerer/uppload
```

#### Installation as extension from TYPO3 Extension Repository (TER) - not recommended

Download and install the [extension][2] with the extension manager module.

### 2.2 Minimal setup

1) Just install the extension and you are done

## 3 Report issues

Please report issue directly in the [issue tracker in the Github repository][3].

## 4 Administration corner

### 4.1 Settings in extension configuration

* **protected_info_uid** - Set the page uid of a page with information why the user has no access to the page, also after login
* **login_page_uid** - Set the page uid of the login page

### 4.2 Changelog

Please have a look into the [Github repository][3].

### 4.3 Release Management

Uppload uses [**semantic versioning**][4], which means, that
* **bugfix updates** (e.g. 1.0.0 => 1.0.1) just includes small bugfixes or security relevant stuff without breaking changes,
* **minor updates** (e.g. 1.0.0 => 1.1.0) includes new features and smaller tasks without breaking changes,
* and **major updates** (e.g. 1.0.0 => 2.0.0) breaking changes wich can be refactorings, features or bugfixes.

### 4.4 Contribution

**Pull Requests** are gladly welcome! Nevertheless please don't forget to add an issue and connect it to your pull requests.
This is very helpful to understand what kind of issue the **PR** is going to solve.

Bugfixes: Please describe what kind of bug your fix solve and give us feedback how to reproduce the issue. We're going
to accept only bugfixes if we can reproduce the issue.

Features: Not every feature is relevant for the bulk of `uppload` users. In addition: We don't want to make `uppload`
even more complicated in usability for an edge case feature. It helps to have a discussion about a new feature before you open a pull request.


[1]: https://getcomposer.org/
[2]: https://extensions.typo3.org/extension/uppload/
[3]: https://github.com/Kephson/uppload
[4]: https://semver.org/


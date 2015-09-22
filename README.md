# EasyAdminPopupBundle

This bundle is a layer that gives the "popup" look and feel for the [EsayAdminBundle](https://github.com/javiereguiluz/EasyAdminBundle)

# Dependency

This bundle requires:

* Jquery
* bootstrap-datetimepicker
* select2
* humane

# Installation

        composer require a5sys/overlay-bundle

In AppKernel.php

        new A5sys\EasyAdminPopupBundle\EasyAdminPopupBundle(),

# Configuration

You have to set the cofiguration:

        easy_admin_popup:
            layout: "::admin_layout.html.twig" #mandatory

The layout must have a body block.

The layout must include:

* Jquery
* bootstrap-datetimepicker
* select2
* humane

And include the JS using assetic:

* '@EasyAdminPopupBundle/Resources/assets/js/form-modal.js'
* '@EasyAdminPopupBundle/Resources/assets/js/humane-error.js'


# Use

Create a controller that extends "A5sys\EasyAdminPopupBundle\Controller\AdminController"

Include this controller in your routing using annotation


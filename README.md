# Sage Eject Blocks

![Latest Stable Version](https://img.shields.io/packagist/v/log1x/sage-eject-blocks?style=flat-square)
![Build Status](https://img.shields.io/circleci/build/github/Log1x/sage-eject-blocks?style=flat-square)
![Total Downloads](https://img.shields.io/packagist/dt/log1x/sage-eject-blocks?style=flat-square)

## So what do we have here?

This package is a 4-5 hour, half-assed attempt/proof of concept to devise a strategy to solve the seperation of concerns that Gutenberg has created in the theme development workflow.

![Preview](https://i.imgur.com/LvZJ76W.gif)

### Current Problem

Gutenberg is amazing, but has destroyed the theme workflow with having to separate concerns between theme + Gutenberg package/plugin. 

If you code your blocks into your theme and the theme changes, your blocks disappear and you risk losing content. Yikes.

### Proposed Solution

This attempts to address that concern by allowing you to eject the block scripts from your theme into a plugin auto-magically on-the-go while still prioritizing loading them from your theme if they exist. Styles still come from your theme and are not included during ejection. Theme goes away? Plugin takes over.

The theme stay themein', the generated plugin functions...if it _has_ too. There are currently no dependencies on Sage 10 or Acorn in the generated plugin, but obviously they are required if you want to use this.

### Avoiding conflicts

Conflict avoidence is currently done using [`wp_script_is()`](https://developer.wordpress.org/reference/functions/wp_script_is/) – but I am more than open to other solutions/ideas.

This allows us to maintain our workflow 100% inside of our theme, and as a freelance developer, agency, etc. – take it upon ourselves to do the right thing, and eject our scripts into a plugin whether it be manually, through continuous integration, etc. so down the road, if the site is to change - they aren't screwed, and you don't have to change how you've been doing things for the past XY years.

### Uhhh...okay?

The concept and CLI flow may be rough around the edges, but I assure you I can make it prettier and feel nicer if this is an idea that would prove to be fruitful and people actually want it.

Maybe this isn't the right way to look at this? Maybe this is useless? I don't know. That's why I'm putting it out there. You tell me.

No, really. Please stop my suffering immediately if this is useless.

### TODO (Maybe?)

- Make CLI prettier.
- Allow more verbose output.
- Allow things to be more programatical (no input required).
- Allow things to be pre-configured (config/whatever.php).
- Assure CI flow is adequate (see 3/4).
- Debate `wp_script_is()` and alternatives.
- Debate what should and should not be configurable for compatibility purposes.
- Assure the plugin loader is written in the best way humanly possible (and works as intended).
- Some kind of `row_action` if the plugin is active (scripts loaded) or lazy-loaded.
- Code check me, please.

## Requirements

- [Sage](https://github.com/roots/sage) >= 10.0
- [PHP](https://secure.php.net/manual/en/install.php) >= 7.2
- [Composer](https://getcomposer.org/download/)

## Installation

Install via Composer:

```bash
$ composer require log1x/sage-eject-blocks
```

## Usage

```bash
$ wp acorn eject:blocks
```

## Bug Reports

Yes.

## Contributing

Yes.

## License

Sage Eject Blocks is provided under the [MIT License](https://github.com/log1x/sage-eject-blocks/blob/master/LICENSE.md).

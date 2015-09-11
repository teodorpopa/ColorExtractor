# ColorExtractor

<img src="https://travis-ci.org/teodorpopa/ColorExtractor.svg?branch=master" /> <a href="https://codeclimate.com/github/teodorpopa/ColorExtractor"><img src="https://codeclimate.com/github/teodorpopa/ColorExtractor/badges/gpa.svg" /></a> <a href='https://coveralls.io/github/teodorpopa/ColorExtractor?branch=master'><img src='https://coveralls.io/repos/teodorpopa/ColorExtractor/badge.svg?branch=master&service=github' alt='Coverage Status' /></a> <img src="https://scrutinizer-ci.com/g/teodorpopa/ColorExtractor/badges/quality-score.png?b=master" />

Based on https://github.com/thephpleague/color-extractor

Extends the functionality to extract also the RGB index and color percentage.

##### Example

```
$colors = new ColorExtractor::load('image.jpg')->extract();
```
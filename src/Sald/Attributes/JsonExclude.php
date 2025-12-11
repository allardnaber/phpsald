<?php

namespace Sald\Attributes;

use Attribute;

/**
 * Exclude the field upon serializing the object to JSON. @todo deserialize
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class JsonExclude {}

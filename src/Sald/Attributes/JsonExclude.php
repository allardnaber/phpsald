<?php

namespace Sald\Attributes;

/**
 * Exclude the field upon serializing the object to JSON. @todo deserialize
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class JsonExclude {}

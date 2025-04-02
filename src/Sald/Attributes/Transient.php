<?php

namespace Sald\Attributes;

/**
 * Used to indicate the field is not read from or written to the database.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Transient {}

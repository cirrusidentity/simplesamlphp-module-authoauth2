<?php

namespace SimpleSAML\Module\authoauth2\locators;

interface ClassResolverInterface
{
    /**
     * Resolve module class.
     *
     * This function takes a string on the form "<module>:<class>" and converts it to a class
     * name. It can also check that the given class is a subclass of a specific class. The
     * resolved classname will be "\SimleSAML\Module\<module>\<$type>\<class>.
     *
     * It is also possible to specify a full classname instead of <module>:<class>.
     *
     * An exception will be thrown if the class can't be resolved.
     *
     * @param string      $id The string we should resolve.
     * @param string      $type The type of the class.
     * @param string|null $subclass The class should be a subclass of this class. Optional.
     *
     * @return string The classname.
     *
     * @throws \Exception If the class cannot be resolved.
     */
    public function resolveClass(string $id, string $type, ?string $subclass = null): string;
}

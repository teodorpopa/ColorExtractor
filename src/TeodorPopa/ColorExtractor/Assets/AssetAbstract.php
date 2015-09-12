<?php

namespace TeodorPopa\ColorExtractor\Assets;

abstract class AssetAbstract
{

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $reflection = new \ReflectionClass(get_called_class());

        $publicProps = $reflection->getProperties();

        foreach($publicProps as $property) {
            $propertyName = $property->getName();

            if(isset($data[$propertyName])) {
                $this->{$propertyName} = $data[$propertyName];
            }
        }
    }

}
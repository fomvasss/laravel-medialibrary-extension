<?php
/**
 * Created by PhpStorm.
 * User: fomvasss
 * Date: 07.01.19
 * Time: 1:32
 */

namespace Fomvasss\MediaLibraryExtension\Models\Traits\HasMedia;

interface HasMedia extends \Spatie\MediaLibrary\HasMedia\HasMedia
{
    /**
     * @return array
     */
    public function getPerformOnImageCollections(): array;

    /**
     * @return array
     */
    public function getMediaFieldsSingle(): array;

    /**
     * @return array
     */
    public function getMediaFieldsMultiple(): array;

    /**
     * @param array $mediaFieldsSingle
     */
    public function setMediaFieldsSingle(array $mediaFieldsSingle);

    /**
     * @param array $mediaFieldsMultiple
     */
    public function setMediaFieldsMultiple(array $mediaFieldsMultiple);

    /**
     * @param string|null $field
     * @return mixed
     */
    public function getMediaFieldsValidation(string $field = null): array;

    /**
     * @param array $rules
     * @return mixed
     */
    public function setMediaFieldsValidation(array $rules = []);

    /**
     * @param array $rules
     * @return mixed
     */
    public function addMediaFieldsValidation(array $rules = []);
}
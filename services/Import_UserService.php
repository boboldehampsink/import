<?php

namespace Craft;

/**
 * Import User Service.
 *
 * Contains logic for importing users
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   http://buildwithcraft.com/license Craft License Agreement
 *
 * @link      http://github.com/boboldehampsink
 */
class Import_UserService extends BaseApplicationComponent implements IImportElementType
{
    /**
     * Return import template.
     *
     * @return string
     */
    public function getTemplate()
    {
        return 'import/types/user/_upload';
    }

    /**
     * Return groups.
     *
     * @return array|bool
     */
    public function getGroups()
    {
        // Check if usergroups are allowed in this installation
        if (craft()->getEdition() == Craft::Pro) {

            // Get usergroups
            $groups = craft()->userGroups->getAllGroups();

            // Return when groups found
            if (count($groups)) {
                return $groups;
            }

            // Still return true when no groups found
            return true;
        }

        // Else, dont proceed with the user element
        return false;
    }

    /**
     * Return user model with group.
     *
     * @param array|object $settings
     *
     * @return UserModel
     */
    public function setModel($settings)
    {
        // Set up new user model
        $element = new UserModel();

        return $element;
    }

    /**
     * Set user criteria.
     *
     * @param array|object $settings
     *
     * @return ElementCriteriaModel
     */
    public function setCriteria($settings)
    {
        // Match with current data
        $criteria = craft()->elements->getCriteria(ElementType::User);
        $criteria->limit = null;
        $criteria->status = isset($settings['map']['status']) ? $settings['map']['status'] : null;

        return $criteria;
    }

    /**
     * Delete users.
     *
     * @param array $elements
     *
     * @return bool
     */
    public function delete(array $elements)
    {
        $return = true;

        // Delete users
        foreach ($elements as $element) {
            if (!craft()->users->deleteUser($element)) {
                $return = false;
            }
        }

        return $return;
    }

    /**
     * Prepare reserved ElementModel values.
     *
     * @param array            &$fields
     * @param BaseElementModel $element
     *
     * @return BaseElementModel
     */
    public function prepForElementModel(array &$fields, BaseElementModel $element)
    {
        $username = Import_ElementModel::HandleUsername;
        $email = Import_ElementModel::HandleEmail;

        foreach ($fields as $handle => $value) {
            switch ($handle) {
                case Import_ElementModel::HandleId:
                case Import_ElementModel::HandleUsername:
                case Import_ElementModel::HandleFirstname:
                case Import_ElementModel::HandleLastname:
                case Import_ElementModel::HandleEmail:
                case Import_ElementModel::HandlePrefLocale:
                case Import_ElementModel::HandlePassword:
                    $element->$handle = $value;
                    unset($fields[$handle]);
                    break;
                case Import_ElementModel::HandlePhoto:
                    $element->$handle = $value;
                    break;
                case Import_ElementModel::HandleStatus:
                    $element->$handle = $value;
                    if ($element->$handle == UserStatus::Pending) {
                        $element->pending = true;
                    }
                    unset($fields[$handle]);

                    break;
                default:
                    continue 2;
            }

        }

        // Set email as username
        if (craft()->config->get('useEmailAsUsername')) {
            $element->$username = $element->$email;
        }

        // Return entry
        return $element;
    }

    /**
     * Save a user.
     *
     * @param BaseElementModel &$element
     * @param array|object     $settings
     *
     * @return bool
     */
    public function save(BaseElementModel &$element, $settings)
    {
        // Save user
        if (craft()->users->saveUser($element)) {

            // Assign to groups
            craft()->userGroups->assignUserToGroups($element->id, $settings['elementvars']['groups']);

            return true;
        }

        return false;
    }

    /**
     * Executes after saving a user.
     *
     * @param array            $fields
     * @param BaseElementModel $element
     */
    public function callback(array $fields, BaseElementModel $element)
    {
        // No callback for users
    }
}

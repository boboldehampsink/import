<?php

namespace Craft;

/**
 * Import Element Model.
 *
 * Contains the constants for element field handles
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   http://buildwithcraft.com/license Craft License Agreement
 *
 * @link      http://github.com/boboldehampsink
 */
class Import_ElementModel extends BaseModel
{
    const HandleId = 'id';
    const HandleLocale = 'locale';
    const HandleTitle = 'title';
    const HandleAuthor = 'authorId';
    const HandlePostDate = 'postDate';
    const HandleExpiryDate = 'expiryDate';
    const HandleEnabled = 'enabled';
    const HandleSlug = 'slug';
    const HandleParent = 'parentId';
    const HandleAncestors = 'ancestors';
    const HandleUsername = 'username';
    const HandlePhoto = 'photo';
    const HandleFirstname = 'firstName';
    const HandleLastname = 'lastName';
    const HandleEmail = 'email';
    const HandleStatus = 'status';
    const HandlePrefLocale = 'preferredLocale';
    const HandlePassword = 'newPassword';
}

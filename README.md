## SilverStripe DataObject Manager

[![Latest Stable Version](https://poser.pugx.org/hudhaifas/silverstripe-dataobject-manager/v/stable)](https://packagist.org/packages/hudhaifas/silverstripe-dataobject-manager) [![Total Downloads](https://poser.pugx.org/hudhaifas/silverstripe-dataobject-manager/downloads)](https://packagist.org/packages/hudhaifas/silverstripe-dataobject-manager) [![Latest Unstable Version](https://poser.pugx.org/hudhaifas/silverstripe-dataobject-manager/v/unstable)](https://packagist.org/packages/hudhaifas/silverstripe-dataobject-manager) [![License](https://poser.pugx.org/hudhaifas/silverstripe-dataobject-manager/license)](https://packagist.org/packages/hudhaifas/silverstripe-dataobject-manager)

DataObject Manager is a standard Fronend wapper fot a certain DataObject instances without relationship with a Page, results to list all, search, show object, and edit obejct.

### [Live Demo](http://dom.hudhaifa.com/)

### Screenshots
- List Objects
![](https://user-images.githubusercontent.com/5335447/34019586-54a8a386-e0f5-11e7-9121-5ea2fcc5f94f.jpg)

- Show Object
![](https://user-images.githubusercontent.com/5335447/34019159-1afe0b3c-e0f3-11e7-860a-b96e6570cf2d.jpg)

- Edit Object
![](https://user-images.githubusercontent.com/5335447/34019776-1594ae8c-e0f6-11e7-819b-c22c61092cde.jpg)

### Features
- Manage a certain DataObject without a relationship with a Page.
- Create a standard Page to show, edit, list, and search for a certain DataObject type.
- Include RichSnippets to the object show page.
- Decrease the response time by using AJAX to load the page content after the page layout is completely loaded to .
- Compatible with Google Custom Search.
- Easy to customize the page layout.

### Limitations
- Create a Page class for each DataObject.
- Edit Only $db and $has_one fields.

### Todos
- Edit many_many relationship fields.
- Waiting for your contributions

## Usage

### Requirements
- SilverStripe Framework 3.x
- SilverStripe CMS 3.x
- SilverStripe Watermarking

### Installation
- Install the module through composer:
`$ composer require hudhaifas/silverstripe-dataobject-manager`
- Run dev/build

## Usage
### ManageableDataObject
- Create / Adjust a class to extend DataObject and implement ManageableDataObject interface to provide the following required data to render your object:

| Function  | Documentation  | Default |
| :------------ |:---------------:| -----:|
| `getObjectItem()`      | Renders object as a list item | `return $this->renderWith('List_Item');` |
| `getObjectTitle()`      | The object title or name | `return $this->getTitle();` |
| `getObjectLink()`      | The link to show the object | `return $this->renderWith('List_Item');` |
| `getObjectEditLink()`      | The link to edit the object | `return $this->renderWith('List_Item');` |
| `getObjectImage()`      | Image of the object | `return $this->Image();` |
| `getObjectDefaultImage()`      | Default image for objects with no image | `return "path/to/images/image.jpg";` |
| `getObjectSummary()`      | Summary data of the object could be array(Title=>Content) or rendered object  | `return array();` |
| `getObjectNav()`      | Extra nav bar  |  |
| `getObjectRelated()`      | List of related objects |  |
| `getObjectTabs()`      | Object's details and content returned in an array(Title=>Content), the content could be text or rendered object |  |
| `isObjectDisabled()`      | Whether the object is disabled | `return flase;` |

- Create / Adjust Page to extend DataObjectPage

| Function  | Documentation  | Required |
| :------------ |:---------------:| -----:|
| `getObjectsList()`      | List of all eligible objects  | Required |
| `restrictFields()`      | Excluded fields from edit |  Optional |
| `searchObjects()`      | Filter objects based on the gived keyword | Optional |
| `getFiltersList()`      | Not implemented yet | Not Required |

### License

    MIT License

    Copyright (c) 2016 Hudhaifa Shatnawi

    Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated 
    documentation files (the "Software"), to deal in the Software without restriction, including without 
    limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of 
    the Software, and to permit persons to whom the Software is furnished to do so, subject to the following
    conditions:

    The above copyright notice and this permission notice shall be included in all copies or substantial portions 
    of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED 
    TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL 
    THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF 
    CONTRACT,  TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER 
    DEALINGS IN THE SOFTWARE.


   [github.com]: <http://github.com/hudhaifas/silverstripe-librarian/issues>

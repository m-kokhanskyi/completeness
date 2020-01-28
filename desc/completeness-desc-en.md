The "Completeness" module measures in percentage the completeness level of the required fields that allows you to control and improve the entity records data quality. In addition, it enables saving entity records, in which required fields are left empty. Please, consider that without the "Completeness" module installed and activated in your system, saving records without filling in all the required fields is not possible.

Thanks to the [graphical representation](#completeness-dashlets), you have full control over the completeness of your entity records data in the system.

## Installation 

To install the "Completeness" module to your system, go to `Administration > Module Manager`, find this module in the "Store" list and click `Install`: 

![Completeness install](_assets/completeness-install.jpg)

Select the desired version in the installation pop-up window that appears and click the `Install` button. The module background will turn green and it will be moved to the "Installed" section of the Module Manager. Click `Run update` to confirm its installation.

> Please, note that running the system update will lead to the logout of all users.

To update/remove the "Completeness" module from the system, use the corresponding options from its single record actions menu in `Administration > Module Manager`. 

## Administrator Functions

The "Completeness" module was created primarily to be used together with the [TreoPIM](https://treopim.com/help/what-is-treopim) system, so further description of the module is given in the context of TreoPIM.

### Module Activation 

To activate the completeness mechanism for a certain entity, go to `Administration > Entity Manager`, select the desired entity (e.g. Product) and click `Edit`:

![Entity Mngr](_assets/entity-mngr.jpg)

In the editing pop-up that opens, select the `Completeness` checkbox and click the `Save` button to apply the changes:

![Entity Editing](_assets/entity-editing.jpg)

Please, note that in the same way you can activate the completeness mechanism for as many entities as needed.

### Marking Entity Fields as Required

The concept of completeness is applied only to the required fields. To mark the *field* as required, go to `Administration > Entity Manager` and click `Fields` for the desired entity:

![Entity Mngr fields](_assets/entity-mngr-fields.jpg)

In the new window that opens, all fields of the selected entity are displayed. Open the desired existing field or create a new one that must be completed and select the `Required` checkbox:

![Required Field](_assets/required-field.jpg)

In such a way, you can mark as required as many entity fields as you need. As a result, the given field(s) will be included to the completeness calculation.

### Marking Product Attributes as Required 

For the `Product` entity, completeness is calculated not only on the basis of the required fields, but also on the basis of the required attributes. You can define product *attributes* as required, both of the `Global` and `Channel` scope. This is performed on the [product family](https://treopim.com/help/product-families) detail view page:

![Required attributes](_assets/required-attributes.jpg)

Linking `Channel` attributes to the product records allows you to have *channel completeness* [calculated](#completeness-calculation-logic) separately for each channel linked to the given product record. However, if there are no required attributes linked to the given channel record, the completeness is calculated only on the basis of its required fields.

Refer to the **TreoPIM user guide** to learn more about the [attributes](https://treopim.com/help/attributes), [channels](https://treopim.com/help/channels), and [product families](https://treopim.com/help/product-families).

### Completeness Value Display Configuration

To add the completeness level display for the previously configured entity, go to `Administration > Layout Manager` and click the given entity in the list to unfold the list of layouts available for this entity. Click the layout you wish to configure (e.g. `List`) and enable the `Total completeness` field by its drag-and-drop from the right column to the left:

![Layout Mngr](_assets/layout-mngr.jpg)

Please, note that total completeness is calculated for the data fields of the default language. However, if you have your field data in [multiple languages](https://treopim.com/store/multi-languages) and wish to calculate and display their completeness level, you can [add](#marking-entity-fields-as-required) the `Completeness > "Locale"` (e.g. `Сompleteness > En_US)`, `Сompleteness > de_DE)`, etc.) fields for the desired entities in the same way, as described above.

Click the `Save` button to complete the operation. The added `Completeness` field(s) will be displayed on the configured layout type for the given entity:

![Completeness added](_assets/completeness-added.jpg)

When the entity record with enabled completeness is edited (e.g. required fields are added, removed, etc.), the completeness percentage is recalculated on the fly.

#### Search Filters

In the same way, completeness levels can also be added to the search filters list in the Layout Manager: 

![Search filters](_assets/search-filters.jpg)

As a result, the enabled filters are added to the filter drop-down list of the configured entity:

![Filter menu](_assets/filter-menu.jpg)

## Completeness Calculation Principles

Having the "Completeness" module installed and properly configured ensures the entity record data calculation in the following ways:

- **Total completeness** – the completeness level of the required fields, including their locales. For product records, the total completeness calculation also includes the required attributes and their locales;

- **Locale completeness** – the completeness level of the required multilingual fields. For product records, the required multilingual `Global` attributes (of the `Boolean`, `Enum`, `Multi-Enum`, `Text`, `Varchar`, and `Wysiwyg` types with the activated `Multi-Language` checkbox) are also included to this calculation. Refer to the [**Multi-Languages module**](https://treopim.com/store/multi-languages) description for details on the multilingual fields and attributes;

- **Completeness** – the completeness level of the required fields and required product attributes. The required multilingual locale fields are not included here, only the main field values. If the "Multi-Languages" nodule is deactivated or removed from the system, the `Total completeness`and `Completeness` values are identical.

Moreover, two additional completeness types are also calculated for [product](https://treopim.com/help/products) records:

- **Global completeness** – the completeness level of the required fields, including their locales. For product records, the total global calculation also includes the required `Global` attributes and their locales
  
- **`Channel` completeness** – the completeness level of the required fields and required `Channel` attributes. If the required `Channel` attribute values are not available, the completeness is calculated on the basis of the required fields only.

## User Functions 

After the "Completeness" module is installed and configured by the administrator, user can view the completeness levels of entity records that are predefined by the [administrator](#administrator-functions) and sort the records in this column accordingly:

![Completeness sorting](_assets/completeness-sorting.jpg)

Also user can filter entity records by their completeness levels in accordance with [search filters](#search-filters), predefined by the administrator:

![Completeness filter added](_assets/completeness-filter-added.jpg)

Moreover, user can influence the completeness statistics by editing the required fields and attributes of the configured entities according to his access rights.

### Completeness Dashlets

In order to conveniently track the completeness of product information in the system, user can display special [dashlets](https://treopim.com/help/dashboards-and-dashlets#dashlets) on his custom dashboard:

![Completeness Dashlets](_assets/completeness-dashlets.jpg)

The following dashlets are available for display:

- **Completeness overview** – total completeness values, including configured locales and channels, in the table view. 
- **Locale completeness** – completeness by locales separately and total, in the graphic form.
- **Channel completeness** – completeness by channels separately and total, in the graphic form.

To learn more about dashboards and dashlets, refer to the corresponding [article](https://treopim.com/help/dashboards-and-dashlets) in the TreoPIM user guide.

***Get the "Completeness" module now to easily control and greatly improve the quality of your product data!***



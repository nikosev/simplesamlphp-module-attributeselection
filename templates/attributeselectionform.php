<?php

use SimpleSAML\Module;

/**
 * Template form for attribute selection.
 *
 * Parameters:
 * - 'srcMetadata': Metadata/configuration for the source.
 * - 'dstMetadata': Metadata/configuration for the destination.
 * - 'yesTarget': Target URL for the yes-button. This URL will receive a POST request.
 * - 'yesData': Parameters which should be included in the yes-request.
 * - 'noTarget': Target URL for the no-button. This URL will receive a GET request.
 * - 'noData': Parameters which should be included in the no-request.
 * - 'attributes': The attributes which are about to be released.
 * - 'sppp': URL to the privacy policy of the destination, or FALSE.
 *
 * @package SimpleSAMLphp
 */
assert('is_array($this->data["srcMetadata"])');
assert('is_array($this->data["dstMetadata"])');
assert('is_string($this->data["yesTarget"])');
assert('is_array($this->data["yesData"])');
assert('is_string($this->data["noTarget"])');
assert('is_array($this->data["noData"])');
assert('is_array($this->data["attributes"])');

// assert('is_array($this->data["hiddenAttributes"])');

assert('is_array($this->data["selectattributes"])');
assert('$this->data["sppp"] === false || is_string($this->data["sppp"])');

// Parse parameters

if (array_key_exists('name', $this->data['srcMetadata'])) {
	$srcName = $this->data['srcMetadata']['name'];
} elseif (array_key_exists('OrganizationDisplayName', $this->data['srcMetadata'])) {
	$srcName = $this->data['srcMetadata']['OrganizationDisplayName'];
} else {
	$srcName = $this->data['srcMetadata']['entityid'];
}

if (is_array($srcName)) {
	$srcName = $this->t($srcName);
}

if (array_key_exists('name', $this->data['dstMetadata'])) {
	$dstName = $this->data['dstMetadata']['name'];
} elseif (array_key_exists('OrganizationDisplayName', $this->data['dstMetadata'])) {
	$dstName = $this->data['dstMetadata']['OrganizationDisplayName'];
} else {
	$dstName = $this->data['dstMetadata']['entityid'];
}

if (is_array($dstName)) {
	$dstName = $this->t($dstName);
}

$srcName = htmlspecialchars($srcName);
$dstName = htmlspecialchars($dstName);
$attributes = $this->data['attributes'];
$selectAttributes = $this->data['selectattributes'];
$this->data['header'] = $this->t('{attributeselection:attributeselection:attribute_selection_header}');
$this->data['head'] = '<link rel="stylesheet" type="text/css" href="/' . $this->data['baseurlpath'] . 'module.php/attributeselection/resources/css/style.css" />' . "\n";
$this->includeAtTemplateBase('includes/header.php');
?>
<p>
	<?php
	if (!empty($this->data['intro'])) {
		echo $this->data['intro'];
	} else {
		echo $this->t('{attributeselection:attributeselection:attribute_selection_accept}', [
			'SPNAME' => $dstName,
			'IDPNAME' => $srcName
		]);
	}

	if (array_key_exists('descr_purpose', $this->data['dstMetadata'])) {
		echo '</p><p>' . $this->t('{attributeselection:attributeselection:attribute_selection_purpose}', [
			'SPNAME' => $dstName,
			'SPDESC' => $this->getTranslation(SimpleSAMLUtilsArrays::arrayize($this->data['dstMetadata']['descr_purpose'], 'en')),
		]);
	}

	?>
</p>

<form style="display: inline; margin: 0px; padding: 0px"
	  action="<?php echo htmlspecialchars($this->data['yesTarget']); ?>">
	<p style="margin: 1em">

		<?php

		foreach ($this->data['yesData'] as $name => $value) {
			echo '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '" />';
		}

		echo '<input type="hidden" name="attributeSelection" />';

		?>
	</p>
	<button type="submit" name="yes" class="btn" id="yesbutton">
		<?php
		echo htmlspecialchars($this->t('{attributeselection:attributeselection:yes}')) ?>
	</button>
</form>

<form style="display: inline; margin-left: .5em;"
      action="<?php echo htmlspecialchars($this->data['noTarget']); ?>"
	  method="get">

	<?php

	foreach ($this->data['noData'] as $name => $value) {
		echo ('<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '" />');
	}

	?>
	<button type="submit" class="btn" name="no" id="nobutton">
		<?php
		echo htmlspecialchars($this->t('{attributeselection:attributeselection:no}')) ?>
	</button>
</form>

<?php

if ($this->data['sppp'] !== false) {
	echo "<p>" . htmlspecialchars($this->t('{attributeselection:attributeselection:attribute_selection_privacy_policy}')) . " ";
	echo "<a target='_blank' href='" . htmlspecialchars($this->data['sppp']) . "'>" . $dstName . "</a>";
	echo "</p>";
}

/**
 * Recursive attribute array listing function
 *
 * @param SimpleSAML\XHTML\Template $t          Template object
 * @param array                     $attributes Attributes to be presented
 * @param string                    $nameParent Name of parent element
 *
 * @return string HTML representation of the attributes
 */

function presentAttributes($t, $attributes, $selectAttributes, $nameParent)
{
	$translator = $t->getTranslator();
	$alternate = [
		'odd',
		'even'
	];
	$i = 0;
	$summary = 'summary="' . $translator->t('{attributeselection:attributeselection:table_summary}') . '"';
	if (strlen($nameParent) > 0) {
		$parentStr = strtolower($nameParent) . '_';
		$str = '<table class="attributes" ' . $summary . '>';
	} else {
		$parentStr = '';
		$str = '<table id="table_with_attributes"  class="attributes" ' . $summary . '>';
		$str .= "\n" . '<caption>' . $translator->t('{attributeselection:attributeselection:table_caption}') . '</caption>';
	}

	foreach ($attributes as $name => $value) {
		$nameRaw = $name;
		if (!empty($selectAttributes[$nameRaw]['description'])) {
			$name = $selectAttributes[$nameRaw]['description'];
		} else {
			$name = $translator->getAttributeTranslation($parentStr . $nameRaw);
		}
		if (count($value) < 2) {
			continue;
		}
		if (preg_match('/^child_/', $nameRaw)) {

			// insert child table

			$parentName = preg_replace('/^child_/', '', $nameRaw);
			foreach ($value as $child) {
				$str .= "\n" . '<tr class="odd"><td style="padding: 2em">' . presentAttributes($t, $child, $selectAttributes, $parentName) . '</td></tr>';
			}
		} else {

			// insert values directly

			$str .= "\n" . '<tr class="' . $alternate[($i++ % 2)] . '"><td><span class="attrname">' . htmlspecialchars($name) . '</span>';
			$str .= '<div class="attrvalue" name="' . htmlspecialchars($nameRaw) . '">';

			// we hawe several values

			$str .= '<ul>';
			if ($nameRaw == 'eduPersonEntitlement') {
				foreach ($value as $listItem) {
					preg_match('/group:(.*)/', $listItem, $shorterValue);
					$valueSplit = explode(':', $shorterValue[1]);
					$groupName = $valueSplit[0];
					preg_match('/role=(.*?)#/', $shorterValue[1], $groupMember);
					$str .= '<li><div title="' . htmlspecialchars($listItem) . '"><input class="attribite-selection" type="' . ($selectAttributes[$nameRaw]['mode'] == 'check' ? 'checkbox' : 'radio') . '" name="' . htmlspecialchars($nameRaw) . '" value="'  . htmlspecialchars($listItem) .  '" /> ' . htmlspecialchars($groupMember[1]) . ' at ' . ($translator->t('{attributeselection:entitlementmapping:' . htmlspecialchars($groupName) . '}') != NULL ? $translator->t('{attributeselection:entitlementmapping:' . htmlspecialchars($groupName) . '}') : htmlspecialchars($groupName)) . '</div></li>';
				}
			} elseif ($nameRaw == 'schacHomeOrganization') {
				foreach ($value as $listItem) {
					preg_match('/(.*)@/', $listItem, $realm);
					preg_match('/@(.*)/', $listItem, $domain);
					$str .= '<li><div title="' . htmlspecialchars($listItem) . '"><input class="attribite-selection" type="' . ($selectAttributes[$nameRaw]['mode'] == 'check' ? 'checkbox' : 'radio') . '" name="' . htmlspecialchars($nameRaw) . '" value="'  . htmlspecialchars($listItem) .  '" /> ' . htmlspecialchars($realm[1]) . ' at ' . ($translator->t('{attributeselection:entitlementmapping:' . htmlspecialchars($groupName) . '}') != NULL ? $translator->t('{attributeselection:organisationmapping:' . htmlspecialchars($domain[1]) . '}') : htmlspecialchars($domain[1])) . '</div></li>';
				}
			} else {
				foreach ($value as $listItem) {
					$str .= '<li><input class="attribite-selection" type="' . ($selectAttributes[$nameRaw]['mode'] == 'check' ? 'checkbox' : 'radio') . '" name="' . htmlspecialchars($nameRaw) . '" value="'  . htmlspecialchars($listItem) .  '" /> ' . htmlspecialchars($listItem) . '</li>';
				}
			}

			$str .= '</ul>';
			$str .= '</div>';

			$str .= '</td></tr>';
		} // end else: not child table
	} // end foreach
	$str .= isset($attributes) ? '</table>' : '';
	return $str;
}

echo '<h3 id="attributeheader">' . $this->t('{attributeselection:attributeselection:attribute_selection_attributes_header}', [
	'SPNAME' => $dstName,
	'IDPNAME' => $srcName
]) . '</h3>';
echo presentAttributes($this, $attributes, $selectAttributes, '');
echo "<script type=\"text/javascript\" src=\"" . htmlspecialchars(Module::getModuleURL('attributeselection/resources/js/jquery-3.3.1.slim.min.js')) . "\"></script>";
echo "<script type=\"text/javascript\" src=\"" . htmlspecialchars(Module::getModuleURL('attributeselection/resources/js/attributeselector.js')) . "\"></script>";
$this->includeAtTemplateBase('includes/footer.php');

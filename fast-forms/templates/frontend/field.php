<?php
/**
 * Frontend field template.
 *
 * @package FastForms
 *
 * @var int    $form_id
 * @var string $instance
 * @var string $field_id
 * @var string $field_name
 * @var string $label
 * @var string $css_class
 * @var bool   $required
 * @var array<string, mixed> $field
 */

use Weblo\FastForms\Support\ConsentHtml;
use Weblo\FastForms\Support\FileAccept;
use Weblo\FastForms\Support\FileUploadHint;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$type         = $field['type'] ?? 'text';
$html_id      = trim( (string) ( $field['htmlId'] ?? '' ) );
$input_id     = $instance . '-' . $field_id;
$wrapper_id   = '' !== $html_id ? ' id="' . esc_attr( $html_id ) . '"' : '';
$input_name   = '' !== $field_name ? $field_name : $field_id;
$placeholder  = $field['placeholder'] ?? '';
$default      = $field['defaultValue'] ?? '';
$hide_label   = ! empty( $field['hideLabel'] );
$consent_text = (string) ( $field['consentText'] ?? '' );
if ( '' === $consent_text && 'consent' === $type ) {
	$consent_text = (string) $label;
}
$content_text = (string) ( $field['contentText'] ?? '' );
if ( '' === $content_text && 'content' === $type ) {
	$content_text = (string) $label;
}

if ( 'content' === $type ) {
	$wrapper_cls = 'ff-field ff-field--content';
	if ( $css_class ) {
		$wrapper_cls .= ' ' . esc_attr( $css_class );
	}
	?>
	<div class="<?php echo esc_attr( $wrapper_cls ); ?>"<?php echo $wrapper_id; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-ff-field-id="<?php echo esc_attr( $field_id ); ?>">
		<?php if ( ! $hide_label && '' !== $label ) : ?>
			<div class="ff-field__label" id="<?php echo esc_attr( $label_id ); ?>"><?php echo esc_html( $label ); ?></div>
		<?php endif; ?>
		<div class="ff-field__control">
			<div class="ff-content">
				<?php echo ConsentHtml::sanitize( $content_text ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
	</div>
	<?php
	return;
}
$wrapper_cls  = 'ff-field ff-field--' . esc_attr( $type );
if ( $css_class ) {
	$wrapper_cls .= ' ' . esc_attr( $css_class );
}
$show_field_label = ! $hide_label && 'checkbox' !== $type && 'consent' !== $type && '' !== $label;
if ( 'consent' === $type && ! $hide_label && '' !== $label ) {
	$show_field_label = true;
}
$label_id = $input_id . '-label';
$field_attrs = '';
$choice_layout = ( isset( $field['choiceLayout'] ) && 'horizontal' === $field['choiceLayout'] ) ? 'horizontal' : 'vertical';
$choice_layout_class = 'horizontal' === $choice_layout ? ' ff-choice-group--layout-horizontal' : ' ff-choice-group--layout-vertical';

if ( 'radio' === $type && ! empty( $field['allowMultiple'] ) ) {
	$field_attrs .= ' data-ff-allow-multiple="1"';

	if ( '' !== ( $field['minSelections'] ?? '' ) ) {
		$field_attrs .= ' data-ff-min-selections="' . esc_attr( (string) $field['minSelections'] ) . '"';
	}

	if ( '' !== ( $field['maxSelections'] ?? '' ) ) {
		$field_attrs .= ' data-ff-max-selections="' . esc_attr( (string) $field['maxSelections'] ) . '"';
	}
}
?>
<div class="<?php echo esc_attr( $wrapper_cls ); ?>"<?php echo $wrapper_id; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-ff-field-id="<?php echo esc_attr( $field_id ); ?>" data-ff-field-key="<?php echo esc_attr( $input_name ); ?>" data-ff-required="<?php echo $required ? '1' : '0'; ?>"<?php echo $field_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $show_field_label ) : ?>
		<label class="ff-field__label" id="<?php echo esc_attr( $label_id ); ?>" for="<?php echo esc_attr( $input_id ); ?>">
			<?php echo esc_html( $label ); ?>
			<?php if ( $required ) : ?>
				<span class="ff-field__required" aria-hidden="true">*</span>
			<?php endif; ?>
		</label>
	<?php endif; ?>

	<div class="ff-field__control">
		<?php
		switch ( $type ) {
			case 'textarea':
				printf(
					'<textarea id="%1$s" name="%2$s" class="ff-input" rows="%3$d" placeholder="%4$s"%5$s>%6$s</textarea>',
					esc_attr( $input_id ),
					esc_attr( $input_name ),
					absint( $field['rows'] ?? 4 ),
					esc_attr( $placeholder ),
					$required ? ' required' : '',
					esc_textarea( $default )
				);
				break;

			case 'select':
				if ( 'horizontal' === $choice_layout ) {
					echo '<div class="ff-radio-group ff-choice-group ff-choice-group--select' . esc_attr( $choice_layout_class ) . '" role="radiogroup" aria-labelledby="' . esc_attr( $label_id ) . '">';
					$option_index = 0;
					foreach ( $field['options'] ?? array() as $option ) {
						$val       = (string) ( $option['value'] ?? '' );
						$lab       = (string) ( $option['label'] ?? $val );
						$option_id = $input_id . '-opt-' . $option_index;
						if ( '' === $val && '' === $lab ) {
							continue;
						}
						if ( '' === $val ) {
							$val = sanitize_title( $lab );
						}
						printf(
							'<label class="ff-radio"><input type="radio" id="%1$s" name="%2$s" class="ff-input" value="%3$s"%4$s%5$s /><span class="ff-radio__text">%6$s</span></label>',
							esc_attr( $option_id ),
							esc_attr( $input_name ),
							esc_attr( $val ),
							checked( $default, $val, false ),
							$required && 0 === $option_index ? ' required' : '',
							esc_html( $lab )
						);
						++$option_index;
					}
					echo '</div>';
					break;
				}

				echo '<select id="' . esc_attr( $input_id ) . '" name="' . esc_attr( $input_name ) . '" class="ff-input"' . ( $required ? ' required' : '' ) . '>';
				echo '<option value="">' . esc_html__( '— Select —', 'fast-forms' ) . '</option>';
				foreach ( $field['options'] ?? array() as $option ) {
					$val = $option['value'] ?? '';
					$lab = $option['label'] ?? $val;
					printf(
						'<option value="%1$s"%2$s>%3$s</option>',
						esc_attr( $val ),
						selected( $default, $val, false ),
						esc_html( $lab )
					);
				}
				echo '</select>';
				break;

			case 'radio':
				$allow_multiple = ! empty( $field['allowMultiple'] );
				$default_values = array();

				if ( '' !== $default ) {
					$default_values = array_map( 'trim', explode( ',', (string) $default ) );
				}

				if ( $allow_multiple ) {
					echo '<div class="ff-choice-group ff-choice-group--multiple' . esc_attr( $choice_layout_class ) . '" role="group" aria-labelledby="' . esc_attr( $label_id ) . '">';
					$option_index = 0;
					foreach ( $field['options'] ?? array() as $option ) {
						$val       = (string) ( $option['value'] ?? '' );
						$lab       = (string) ( $option['label'] ?? $val );
						$option_id = $input_id . '-opt-' . $option_index;
						if ( '' === $val && '' === $lab ) {
							continue;
						}
						if ( '' === $val ) {
							$val = sanitize_title( $lab );
						}
						$is_checked = in_array( $val, $default_values, true );
						printf(
							'<label class="ff-checkbox ff-checkbox--choice"><input type="checkbox" id="%1$s" name="%2$s[]" class="ff-input" value="%3$s"%4$s /><span class="ff-checkbox__text">%5$s</span></label>',
							esc_attr( $option_id ),
							esc_attr( $input_name ),
							esc_attr( $val ),
							checked( $is_checked, true, false ),
							esc_html( $lab )
						);
						++$option_index;
					}
					echo '</div>';
					break;
				}

				echo '<div class="ff-radio-group ff-choice-group' . esc_attr( $choice_layout_class ) . '" role="radiogroup" aria-labelledby="' . esc_attr( $label_id ) . '">';
				$option_index = 0;
				foreach ( $field['options'] ?? array() as $option ) {
					$val       = (string) ( $option['value'] ?? '' );
					$lab       = (string) ( $option['label'] ?? $val );
					$option_id = $input_id . '-opt-' . $option_index;
					if ( '' === $val && '' === $lab ) {
						continue;
					}
					if ( '' === $val ) {
						$val = sanitize_title( $lab );
					}
					printf(
						'<label class="ff-radio"><input type="radio" id="%1$s" name="%2$s" class="ff-input" value="%3$s"%4$s%5$s /><span class="ff-radio__text">%6$s</span></label>',
						esc_attr( $option_id ),
						esc_attr( $input_name ),
						esc_attr( $val ),
						checked( $default, $val, false ),
						$required && 0 === $option_index ? ' required' : '',
						esc_html( $lab )
					);
					++$option_index;
				}
				echo '</div>';
				break;

			case 'checkbox':
				printf(
					'<label class="ff-checkbox"><input type="checkbox" id="%1$s" name="%2$s" value="1" class="ff-input"%3$s /> <span class="ff-checkbox__text">%4$s</span></label>',
					esc_attr( $input_id ),
					esc_attr( $input_name ),
					$required ? ' required' : '',
					esc_html( $label )
				);
				break;

			case 'consent':
				printf(
					'<label class="ff-checkbox ff-checkbox--consent"><input type="checkbox" id="%1$s" name="%2$s" value="1" class="ff-input"%3$s /><span class="ff-checkbox__text">%4$s</span></label>',
					esc_attr( $input_id ),
					esc_attr( $input_name ),
					$required ? ' required' : '',
					ConsentHtml::sanitize( $consent_text )
				);
				break;

			case 'file':
				$allowed_raw     = trim( (string) ( $field['allowedTypes'] ?? '' ) );
				$accept_attr     = FileAccept::build( $allowed_raw );
				$accept          = '' !== $accept_attr ? ' accept="' . esc_attr( $accept_attr ) . '"' : '';
				$max_kb          = absint( $field['maxFileSize'] ?? 0 );
				$allow_multiple  = ! empty( $field['allowMultiple'] );
				$max_files       = $field['maxFiles'] ?? '';
				$min_files       = $field['minFiles'] ?? '';
				$button_text     = trim( (string) ( $field['fileButtonText'] ?? '' ) );
				$file_layout     = 'horizontal' === $choice_layout ? 'horizontal' : 'vertical';
				$show_upload_hint = ! empty( $field['showUploadHint'] );
				$input_name_attr = $allow_multiple ? $input_name . '[]' : $input_name;
				$multiple_attr   = $allow_multiple ? ' multiple' : '';
				$choose_label    = '' !== $button_text
					? esc_html( $button_text )
					: esc_html( $allow_multiple ? __( 'Choose files', 'fast-forms' ) : __( 'Choose file', 'fast-forms' ) );
				$remove_label    = esc_attr__( 'Remove file', 'fast-forms' );
				$upload_attrs    = ' data-ff-max-size="' . esc_attr( (string) $max_kb ) . '" data-ff-allowed-types="' . esc_attr( $allowed_raw ) . '"';

				if ( $allow_multiple ) {
					$upload_attrs .= ' data-ff-multiple="1"';
				}

				if ( '' !== $max_files ) {
					$upload_attrs .= ' data-ff-max-files="' . esc_attr( (string) $max_files ) . '"';
				}

				if ( '' !== $min_files ) {
					$upload_attrs .= ' data-ff-min-files="' . esc_attr( (string) $min_files ) . '"';
				}

				$wrap_attrs = '';
				if ( $allow_multiple ) {
					$wrap_attrs .= ' data-ff-multiple="1"';
				}
				if ( '' !== $max_files ) {
					$wrap_attrs .= ' data-ff-max-files="' . esc_attr( (string) $max_files ) . '"';
				}
				if ( '' !== $min_files ) {
					$wrap_attrs .= ' data-ff-min-files="' . esc_attr( (string) $min_files ) . '"';
				}

				echo '<div class="ff-file-upload ff-file-upload--' . esc_attr( $file_layout ) . '"' . $wrap_attrs . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<div class="ff-file-upload__row">';
				printf(
					'<label class="ff-file-upload__choose" for="%1$s"><span class="ff-file-upload__choose-text">%2$s</span></label>',
					esc_attr( $input_id ),
					$choose_label
				);
				printf(
					'<input type="file" id="%1$s" name="%2$s" class="ff-input ff-input--file"%3$s%4$s%5$s%6$s />',
					esc_attr( $input_id ),
					esc_attr( $input_name_attr ),
					$accept,
					$multiple_attr,
					$required ? ' required' : '',
					$upload_attrs
				);
				echo '</div>';
				if ( $show_upload_hint ) {
					printf(
						'<p class="ff-file-upload__hint">%1$s</p>',
						esc_html( FileUploadHint::build( $field ) )
					);
				}
				printf(
					'<p class="ff-file-upload__empty">%1$s</p>',
					esc_html__( 'No file selected', 'fast-forms' )
				);
				echo '<ul class="ff-file-upload__list" hidden aria-live="polite"></ul>';
				echo '</div>';
				break;

			case 'range':
				$min_val   = '' !== ( $field['min'] ?? '' ) ? (string) $field['min'] : '';
				$max_val   = '' !== ( $field['max'] ?? '' ) ? (string) $field['max'] : '';
				$step_val  = '' !== ( $field['step'] ?? '' ) ? (string) $field['step'] : '';
				$range_val = '' !== $default ? (string) $default : ( '' !== $min_val ? $min_val : '0' );
				$output_id = $input_id . '-value';
				$extra     = '';
				if ( '' !== $min_val ) {
					$extra .= ' min="' . esc_attr( $min_val ) . '"';
				}
				if ( '' !== $max_val ) {
					$extra .= ' max="' . esc_attr( $max_val ) . '"';
				}
				if ( '' !== $step_val ) {
					$extra .= ' step="' . esc_attr( $step_val ) . '"';
				}
				echo '<div class="ff-range">';
				echo '<div class="ff-range__track-wrap">';
				printf(
					'<output class="ff-range__value" id="%1$s" for="%2$s">%3$s</output>',
					esc_attr( $output_id ),
					esc_attr( $input_id ),
					esc_html( $range_val )
				);
				printf(
					'<input type="range" id="%1$s" name="%2$s" class="ff-input ff-input--range" value="%3$s" data-ff-range-output="%4$s"%5$s%6$s />',
					esc_attr( $input_id ),
					esc_attr( $input_name ),
					esc_attr( $range_val ),
					esc_attr( $output_id ),
					$extra,
					$required ? ' required' : ''
				);
				echo '</div>';
				echo '</div>';
				break;

			case 'star_rating':
				$star_min     = '' !== ( $field['min'] ?? '' ) ? absint( $field['min'] ) : 1;
				$star_max     = '' !== ( $field['max'] ?? '' ) ? absint( $field['max'] ) : 5;
				$star_min     = max( 1, min( 20, $star_min ) );
				$star_max     = max( $star_min, min( 20, $star_max ) );
				$star_default = '' !== $default ? absint( $default ) : 0;

				if ( $star_default > 0 ) {
					$star_default = max( $star_min, min( $star_max, $star_default ) );
				}

				echo '<div class="ff-star-rating" data-ff-min="' . esc_attr( (string) $star_min ) . '" data-ff-max="' . esc_attr( (string) $star_max ) . '">';
				printf(
					'<input type="hidden" id="%1$s" name="%2$s" class="ff-input ff-input--star-rating" value="%3$s"%4$s />',
					esc_attr( $input_id ),
					esc_attr( $input_name ),
					esc_attr( $star_default > 0 ? (string) $star_default : '' ),
					$required ? ' required' : ''
				);
				echo '<div class="ff-star-rating__stars" role="radiogroup" aria-labelledby="' . esc_attr( $label_id ) . '">';
				for ( $star = $star_min; $star <= $star_max; $star++ ) {
					$is_active = $star_default > 0 && $star <= $star_default;
					printf(
						'<button type="button" class="ff-star-rating__star%1$s" data-value="%2$d" aria-label="%3$s"><span aria-hidden="true">★</span></button>',
						$is_active ? ' is-active' : '',
						$star,
						esc_attr(
							sprintf(
								/* translators: 1: star value, 2: max stars */
								__( '%1$d of %2$d stars', 'fast-forms' ),
								$star,
								$star_max
							)
						)
					);
				}
				echo '</div>';
				echo '</div>';
				break;

			default:
				$html_type = in_array( $type, array( 'email', 'tel', 'url', 'number', 'date' ), true ) ? $type : 'text';
				$extra     = '';
				if ( 'number' === $type ) {
					if ( '' !== ( $field['min'] ?? '' ) ) {
						$extra .= ' min="' . esc_attr( (string) $field['min'] ) . '"';
					}
					if ( '' !== ( $field['max'] ?? '' ) ) {
						$extra .= ' max="' . esc_attr( (string) $field['max'] ) . '"';
					}
					if ( '' !== ( $field['step'] ?? '' ) ) {
						$extra .= ' step="' . esc_attr( (string) $field['step'] ) . '"';
					}
				}
				if ( in_array( $type, array( 'text', 'email', 'tel', 'url', 'textarea' ), true ) ) {
					if ( '' !== ( $field['minLength'] ?? '' ) ) {
						$extra .= ' minlength="' . esc_attr( (string) $field['minLength'] ) . '"';
					}
					if ( '' !== ( $field['maxLength'] ?? '' ) ) {
						$extra .= ' maxlength="' . esc_attr( (string) $field['maxLength'] ) . '"';
					}
				}
				printf(
					'<input type="%1$s" id="%2$s" name="%3$s" class="ff-input" value="%4$s" placeholder="%5$s"%6$s%7$s />',
					esc_attr( $html_type ),
					esc_attr( $input_id ),
					esc_attr( $input_name ),
					esc_attr( $default ),
					esc_attr( $placeholder ),
					$extra,
					$required ? ' required' : ''
				);
		}
		?>
	</div>
</div>

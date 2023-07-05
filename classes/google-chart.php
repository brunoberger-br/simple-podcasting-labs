<?php

/*
#0274be   - blue
#02be94   - green
#e17b29   - orange
#f3d612   - yellow
#7c3fea   - purple
#c65fe8   - pink
 */
function google_chart( $chart_data ) {
	if ( count( $chart_data['rows'] ) > 0 ) {
		$script = "<script type='text/javascript'>\n";
		$script .= "google.load('visualization', '1', { packages: ['corechart'],\n";
		$script .= "callback: function () {\n";
		$script .= "	var data = google.visualization.arrayToDataTable([\n";
		$script .= sprintf( "['" . implode( "','", $chart_data['headers'] ) . "'],\n" );
		foreach ( $chart_data['rows'] as $row ) {
			$script .= sprintf( "[" . implode( ",", $row ) . "],\n" );
		}
		$script .= "]);\n";

		//formatter
		if ( isset( $chart_data['formatter'] ) && $chart_data['formatter'] ) {
			$script .= sprintf( "var formatter = new google.visualization.%s( {prefix: '%s'});\n", $chart_data['formatter']['type'], $chart_data['formatter']['prefix'] );
			$script .= "formatter.format(data, 1)\n";
		}

		// options
		$script .= "var options = null;\n";
		if ( $chart_data['options'] ) {
			$script .= "options = {\n";
			if ( ! $chart_data['options']['colors'] ) {
				$script .= "colors: ['#0274be', '#02be94', '#e17b29', '#f3d612', '#7c3fea', '#c65fe8'],";
			}
			foreach ( $chart_data['options'] as $k => $v ) {
				$script .= sprintf( "%s: %s,\n", $k, $v );
			}
			$script .= "};\n";
		}

		$script .= sprintf( "var chart = new google.visualization.%s(document.getElementById('%s'));\n", $chart_data['chart_type'], $chart_data['chart_div'] );
		$script .= "chart.draw(data, options); }\n";
		$script .= "});\n";
		$script .= "</script>";
	} else {
		$script = "<script>jQuery(document).ready(function() {\n";
		$script .= sprintf( " jQuery('#%s').hide(); \n", $chart_data['chart_div'] );
		$script .= "});</script>\n";
	}

	return $script;
}

?>

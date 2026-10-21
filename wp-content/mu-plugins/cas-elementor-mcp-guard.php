<?php
/**
 * Remove two REST surfaces that run MCP tools for any editor or logged-in user (review 2026-10-20).
 *
 * - /elementor/v1/mcp-proxy runs Elementor MCP tools, including destructive element edits. Its only
 *   check is edit_posts (modules/mcp/rest-api/mcp-proxy-rest-api.php, lines 34 and 49). It works even
 *   while elementor_mcp_enabled is off.
 * - /mcp/mcp-adapter-default-server is registered by the vendored MCP adapter for any logged-in user.
 *
 * Routes are removed in the rest_endpoints filter, so both answer 404 rest_no_route for everyone.
 * Elementor's own MCP server (/elementor/mcp) is not listed. It exists only while the admin-enabled
 * switch is on, and that switch is the owner's control.
 */
defined( 'ABSPATH' ) || exit;

const CAS_ELEMENTOR_MCP_GUARD_ROUTES = array(
	'/elementor/v1/mcp-proxy',
	'/mcp/mcp-adapter-default-server',
);

/* The route itself, or a sub-path or regex variant (the next character is '/' or '('). A route that
   only starts with the same letters (for example mcp-proxy-other), is left alone. */
function cas_elementor_mcp_guard_matches( $route ) {
	foreach ( CAS_ELEMENTOR_MCP_GUARD_ROUTES as $prefix ) {
		if ( $route === $prefix || 0 === strpos( $route, $prefix . '/' ) || 0 === strpos( $route, $prefix . '(' ) ) {
			return true;
		}
	}
	return false;
}

/* Priority 1000 runs after every other filter on the route table. */
add_filter( 'rest_endpoints', function ( $endpoints ) {
	if ( ! is_array( $endpoints ) ) {
		return $endpoints; // Existence guard: no route table, nothing to remove.
	}
	foreach ( array_keys( $endpoints ) as $route ) {
		if ( is_string( $route ) && cas_elementor_mcp_guard_matches( $route ) ) {
			unset( $endpoints[ $route ] );
		}
	}
	return $endpoints;
}, 1000 );

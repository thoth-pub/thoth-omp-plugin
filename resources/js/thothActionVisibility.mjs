export function getThothActionVisibility({
	hasWorkLink,
	workStatus,
	statusRequestCompleted,
	workNotFound,
	fetchError,
	isPublished,
}) {
	const canShowLinkedWorkActions =
		hasWorkLink &&
		statusRequestCompleted &&
		Boolean(workStatus) &&
		!workNotFound &&
		!fetchError;

	return {
		view: canShowLinkedWorkActions,
		unlink:
			hasWorkLink && statusRequestCompleted && workNotFound && !fetchError,
		update: canShowLinkedWorkActions && !isPublished,
		register: !hasWorkLink,
	};
}

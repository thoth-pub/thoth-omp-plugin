export function openUnlinkWorkConfirmation({
	openDialog,
	title,
	message,
	cancelLabel,
	onConfirm,
}) {
	openDialog({
		title,
		message,
		actions: [
			{
				label: title,
				isWarnable: true,
				callback(close) {
					close();
					onConfirm();
				},
			},
			{
				label: cancelLabel,
				callback: (close) => close(),
			},
		],
		modalStyle: 'negative',
	});
}

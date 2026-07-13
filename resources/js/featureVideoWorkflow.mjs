export function getFormComposable(useFormModule) {
	return useFormModule.useForm;
}

export function getTranslator(useLocalizeModule) {
	const {useLocalize} = useLocalizeModule;
	const {t} = useLocalize();

	return t;
}

export function addFeatureVideoMenuItem(menuItems, label) {
	return menuItems.map((menuItem) => {
		if (menuItem.key !== 'marketing') {
			return menuItem;
		}

		const items = [...(menuItem.items || [])];
		if (items.some(({key}) => key === 'marketing_featureVideo')) {
			return menuItem;
		}

		const publicationDatesIndex = items.findIndex(
			({key}) => key === 'marketing_publicationDates',
		);
		items.splice(publicationDatesIndex + 1, 0, {
			key: 'marketing_featureVideo',
			label,
			state: {
				primaryMenuItem: 'marketing',
				secondaryMenuItem: 'featureVideo',
				title: label,
			},
		});

		return {...menuItem, items};
	});
}

export function getFeatureVideoPrimaryItems(primaryItems, args) {
	if (
		args?.selectedMenuState?.primaryMenuItem !== 'marketing' ||
		args?.selectedMenuState?.secondaryMenuItem !== 'featureVideo'
	) {
		return primaryItems;
	}

	return [
		{
			component: 'FeatureVideoForm',
			props: {submission: args.submission},
		},
	];
}

/**
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. See docs/COPYING.
 */
import {seedLinkedDraftBook, readRegisteredWork} from '../../support/thoth';

describe('Thoth metadata synchronization', function () {
	beforeEach(function () {
		cy.server();
		seedLinkedDraftBook().as('book');
	});

	it('updates the existing Thoth work with current OMP metadata', function () {
		// The remote title is deliberately older than the local publication metadata.
		readRegisteredWork(this.book.key).its('title').should('eq', this.book.oldTitle);
		cy.login('admin', 'admin', 'publicknowledge');
		cy.visit(`/index.php/publicknowledge/workflow/access/${this.book.submissionId}`);
		cy.get('#publication-button').click();
		cy.get('.pkpPublication').should('be.visible');

		// Explicitly synchronize the linked draft through the plugin action.
		cy.route('PUT', `**/submissions/${this.book.submissionId}/publications/${this.book.publicationId}/synchronize`)
			.as('synchronize');
		cy.route('POST', '**/notification/fetchNotification').as('notification');
		cy.get('.pkpPublication__thoth')
			.contains('a', /^\s*Update Metadata\s*$/).scrollIntoView().click();
		cy.wait('@synchronize').its('status').should('eq', 200);
		cy.wait('@notification').its('status').should('eq', 200);
		// The OMP modal disables pointer events outside it, so Cypress's fixed-element
		// visibility check misclassifies this toast as covered. Check its rendered content.
		cy.get('.app__notifications .pkpNotification--success')
			.should('contain.text', 'Submission metadata successfully sent to Thoth.');

		// A changed title on the same remote ID proves that synchronization took place.
		readRegisteredWork(this.book.key).then((work) => {
			expect(work.workId).to.eq(this.book.workId);
			expect(work.title).to.eq(this.book.title);
			expect(work.title).not.to.eq(this.book.oldTitle);
			expect(work.workStatus).to.eq('FORTHCOMING');
		});
	});
});

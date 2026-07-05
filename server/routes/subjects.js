const express = require('express');
const router = express.Router();
const subjectController = require('../controllers/subjectController');
const { protect } = require('../middlewares/auth');
const { requireRole } = require('../middlewares/roleMiddleware');

router.use(protect);

router.get('/', subjectController.getAllSubjects);
router.get('/:id', subjectController.getSubject);

// Only admin/staff can modify subjects
router.use(requireRole('admin', 'staff'));

router.post('/', subjectController.createSubject);
router.put('/:id', subjectController.updateSubject);
router.delete('/:id', subjectController.deleteSubject);

module.exports = router;

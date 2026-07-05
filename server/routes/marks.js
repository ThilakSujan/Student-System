const express = require('express');
const router = express.Router();
const markController = require('../controllers/markController');
const { protect } = require('../middlewares/auth');
const { requireRole } = require('../middlewares/roleMiddleware');

router.use(protect);

router.get('/', markController.getAllMarks);

router.use(requireRole('admin', 'staff'));
router.post('/', markController.addMarks);
router.put('/:id', markController.updateMark);
router.post('/publish', markController.publishMarks);

module.exports = router;

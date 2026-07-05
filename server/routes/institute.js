const express = require('express');
const router = express.Router();
const instituteController = require('../controllers/instituteController');
const { protect } = require('../middlewares/auth');
const { requireRole } = require('../middlewares/roleMiddleware');

router.use(protect);

router.get('/', instituteController.getInstitute);
router.put('/', requireRole('admin'), instituteController.updateInstitute);

module.exports = router;

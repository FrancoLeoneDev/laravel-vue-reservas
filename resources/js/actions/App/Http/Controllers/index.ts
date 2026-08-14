import ServiceCatalogController from './ServiceCatalogController'
import BookingController from './BookingController'
import Admin from './Admin'
import Settings from './Settings'
const Controllers = {
    ServiceCatalogController: Object.assign(ServiceCatalogController, ServiceCatalogController),
BookingController: Object.assign(BookingController, BookingController),
Admin: Object.assign(Admin, Admin),
Settings: Object.assign(Settings, Settings),
}

export default Controllers